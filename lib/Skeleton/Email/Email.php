<?php
/**
 * Email class
 *
 * Send emails
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Email;

class Email {
	/**
	 * Email type
	 *
	 * @access private
	 * @var string $type
	 */
	private $type = '';

	/**
	 * Sender
	 *
	 * @access private
	 * @var array $sender
	 */
	private $sender = null;

	/**
	 * Recipients
	 *
	 * @access private
	 * @var array $recipients
	 */
	private $recipients = [];

	/**
	 * Assigned variables
	 *
	 * @access private
	 * @var array $assigns
	 */
	private $assigns = [];

	/**
	 * Translation
	 *
	 * @access private
	 * @var \Skeleton\I18n\Translation $translation
	 */
	private $translation = null;

	/**
	 * Files
	 *
	 * @access private
	 * @var array $files
	 */
	private $files = [];

	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $type
	 */
	public function __construct($type) {
		if ($type === null) {
			throw new \Exception('No email type specified');
		}
		$this->type = $type;
	}

	/**
	 * Add recipient TO
	 *
	 * @access private
	 * @param string $email
	 * @param string $name
	 */
	public function add_to($email, $name = null) {
		$this->recipients['to'][] = [
			'name' => $name,
			'email' => $email,
		];
	}

	/**
	 * Add recipient CC
	 *
	 * @access private
	 * @param string $email
	 * @param string $name
	 */
	public function add_cc($email, $name = null) {
		$this->recipients['cc'][] = [
			'name' => $name,
			'email' => $email,
		];
	}

	/**
	 * Add recipient CC
	 *
	 * @access private
	 * @param string $email
	 * @param string $name
	 */
	public function add_bcc($email, $name = null) {
		$this->recipients['bcc'][] = [
			'name' => $name,
			'email' => $email,
		];
	}

	/**
	 * Set translation
	 *
	 * @access public
	 * @param \Skeleton\I18n\Translation $translation
	 */
	public function set_translation(\Skeleton\I18n\Translation $translation) {
		$this->translation = $translation;
	}

	/**
	 * Add a file
	 *
	 * @access public
	 * @param File $file
	 */
	public function add_attachment(\Skeleton\File\File $file) {
		$this->files[] = $file;
	}

	/**
	 * Set sender
	 *
	 * @param string $email
	 * @param string $address
	 */
	public function set_sender($email, $name = null) {
		$this->sender = [
			'name' => $name,
			'email' => $email,
		];
	}

	/**
	 * Assign
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public function assign($key, $value) {
		$this->assigns[$key] = $value;
	}

	/**
	 * Send email
	 *
	 * @access public
	 */
	public function send() {
		if (!$this->validate($errors)) {
			throw new \Exception('Cannot send email, Mail not validated. Errored fields: ' . implode(', ', $errors));
		}

		if (Config::$archive_mailbox !== null) {
			$this->add_bcc(Config::$archive_mailbox);
		}

		$template = new \Skeleton\Template\Template();
		if ($this->translation !== null) {
			$template->set_translation($this->translation);
		}
		$template->set_template_directory(Config::$email_directory . '/template/');

		foreach ($this->assigns as $key => $value) {
			$template->assign($key, $value);
		}

		$transport = \Swift_MailTransport::newInstance();
		$mailer = \Swift_Mailer::newInstance($transport);
		$message = \Swift_Message::newInstance()
			->setBody($template->render( $this->type . '/html.twig'), 'text/html')
			->addPart($template->render( $this->type . '/text.twig' ), 'text/plain')
			->setSubject($template->render( $this->type . '/subject.twig' ))
		;

		if (isset($this->sender['name'])) {
			$message->setFrom([$this->sender['email'] => $this->sender['name']]);
		} else {
			$message->setFrom($this->sender['email']);
		}

		$this->add_html_images($message);
		$this->attach_files($message);

		foreach ($this->recipients as $type => $recipients) {
			foreach ($recipients as $recipient) {
				if ($recipient['name'] != '') {
					$addresses[$recipient['email']] = $recipient['name'];
				} else {
					$addresses[] = $recipient['email'];
				}
			}

			$set_to = 'set' . ucfirst($type);
			call_user_func([$message, $set_to], $addresses);
		}

		$mailer->send($message);
		unset($template);
	}

	/**
	 * Validate
	 *
	 * @access private
	 * @return bool $validated
	 * @param array $errors
	 */
	public function validate(&$errors = []) {
		if (!isset($this->type)) {
			$errors[] = 'type';
		}

		if (!isset($this->sender['email'])) {
			$errors[] = 'sender[email]';
		}

		if (!isset($this->recipients) or count($this->recipients) == 0) {
			$errors[] = 'recipients';
		}

		if (count($errors) == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Add embedded HTML images (image dir)
	 *
	 * @access protected
	 * @param Swift_Message $message
	 */
	protected function add_html_images(&$message) {
		$path = Config::$email_directory . '/media/';
		if (!file_exists($path)) {
			return;
		}

		$html_body = $message->getBody();

		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if (substr($file,0,1) != '.' && strpos($html_body, $file) !== false) {
					$swift_image = \Swift_Image::newInstance(file_get_contents($path . $file), $file, Util::mime_type($path . $file));
					$html_body = str_replace($file, $message->embed($swift_image), $html_body);
				}
			}
		}

		$message->setBody($html_body);

		closedir($handle);
	}

	/**
	 * Attach files
	 *
	 * @access private
	 * @param Swift_Message $message
	 */
	private function attach_files(&$message) {
		foreach ($this->files as $file) {
			$message->attach(\Swift_Attachment::fromPath($file->get_path())->setFilename($file->name));
		}
	}
}
