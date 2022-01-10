<?php
/*
 * This is a local fork of Symfony Mailer's Sendmail transport.
 * https://github.com/symfony/mailer/blob/5.4/Transport/SendmailTransport.php
 *
 * It mostly fixes the issue with the original version's sendmail -t -i not
 * working with BCC recipients. It does not support -bs, as we don't currently
 * use it anywhere, and there is no (published) clean way to fix it.
 *
 * THe modifications to this file are based on PR #39744
 * https://github.com/symfony/symfony/pull/39744
 *
 * -- Gerry Demaret, Tigron bv <gerry@tigron.be>
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code. (Symfony Mailer)
 */

namespace Skeleton\Email\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\AbstractStream;
use Symfony\Component\Mailer\Transport\Smtp\Stream\ProcessStream;
use Symfony\Component\Mime\RawMessage;

/**
 * SendmailTransport for sending mail through a Sendmail/Postfix (etc..) binary.
 *
 * Supported modes are -bs and -t, with any additional flags desired.
 * It is advised to use -bs mode since error reporting with -t mode is not
 * possible.
 *
 * Transport can be instanciated through SendmailTransportFactory or NativeTransportFactory:
 *
 * - SendmailTransportFactory to use most common sendmail path and recommanded options
 * - NativeTransportFactory when configuration is set via php.ini
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Chris Corbyn
 */
class Sendmail extends \Symfony\Component\Mailer\Transport\AbstractTransport
{
	private $command = '/usr/sbin/sendmail -bs';
	private $stream;
	private $transport;

	/**
	 * Constructor.
	 *
	 * If using -t mode you are strongly advised to include -oi or -i in the flags.
	 * For example: /usr/sbin/sendmail -oi -t
	 * -f<sender> flag will be appended automatically if one is not present.
	 *
	 * The recommended mode is "-bs" since it is interactive and failure notifications are hence possible.
	 */
	public function __construct(string $command = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null) {
		parent::__construct($dispatcher, $logger);

		if (null !== $command) {
			if (!str_contains($command, ' -bs') && !str_contains($command, ' -t')) {
				throw new \InvalidArgumentException(sprintf('Unsupported sendmail command flags "%s"; must be one of "-bs" or "-t" but can include additional flags.', $command));
			}

			$this->command = $command;
		}

		$this->stream = new ProcessStream();
		if (str_contains($this->command, ' -bs')) {
			$this->stream->setCommand($this->command);
			$this->transport = new SmtpTransport($this->stream, $dispatcher, $logger);
		}
	}

	public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage {
		if ($this->transport) {
			return $this->transport->send($message, $envelope);
		}

		return parent::send($message, $envelope);
	}

	public function __toString(): string {
		if ($this->transport) {
			return (string) $this->transport;
		}

		return 'smtp://sendmail';
	}

	protected function doSend(SentMessage $message): void {
		$this->getLogger()->debug(sprintf('Email transport "%s" starting', __CLASS__));

		$command = $this->command;
		if (!str_contains($command, ' -f')) {
			$command .= ' -f'.escapeshellarg($message->getEnvelope()->getSender()->getEncodedAddress());
		}

		$chunks = AbstractStream::replace("\r\n", "\n", $message->toIterable());

		if (!str_contains($command, ' -i') && !str_contains($command, ' -oi')) {
			$chunks = AbstractStream::replace("\n.", "\n..", $chunks);
		}

		$this->stream->setCommand($command);
		$this->stream->initialize();

		if (false !== strpos($command, ' -t')) {
			$email = $message->getOriginalMessage();

			if ($email instanceof Email) {
				foreach ($email->getBcc() as $recipient) {
					$this->stream->write('Bcc:'.$recipient->toString()."\n");
				}
			}
		} else {
			// See if we can come up with a better fix than the one suggested:
			// https://github.com/symfony/symfony/pull/39744
			//
			// As we don't currently use this, delay support until we need it
			throw new \TransportException(sprintf('The -bs sendmail command flag is currently not fully supported "%s".', $command));
		}

		foreach ($chunks as $chunk) {
			$this->stream->write($chunk);
		}

		$this->stream->flush();
		$this->stream->terminate();

		$this->getLogger()->debug(sprintf('Email transport "%s" stopped', __CLASS__));
	}
}
