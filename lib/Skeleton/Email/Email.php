<?php
/**
 * Email class
 *
 * Send emails
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 * @author Lionel Laffineur <lionel@tigron.be>
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
	 * Envelope from
	 *
	 * @access private
	 * @var array $envelope_from
	 */
	private $envelope_from = null;

	/**
	 * Recipients
	 *
	 * @access protected
	 * @var array $recipients
	 */
	protected $recipients = [];

	/**
	 * Reply to
	 *
	 * @access private
	 * @var array $reply_to
	 */
	private $reply_to = [];

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
	 * Template directories
	 *
	 * @access private
	 * @var array $template_paths
	 */
	private $template_paths = [];

	/**
	 * Headers
	 *
	 * @access private
	 * @var array $headers
	 */
	private $headers = [];

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
		if ($this->addressee_exists($email, ['to'])) {
			return;
		}

		if ($name !== null) {
			$name = trim($name);
		}

		$this->recipients['to'][] = [
			'name' => $name,
			'email' => strtolower($email),
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
		if ($this->addressee_exists($email, ['cc'])) {
			return;
		}

		if ($name !== null) {
			$name = trim($name);
		}

		$this->recipients['cc'][] = [
			'name' => $name,
			'email' => strtolower($email),
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
		if ($this->addressee_exists($email, ['bcc'])) {
			return;
		}

		if ($name !== null) {
			$name = trim($name);
		}

		$this->recipients['bcc'][] = [
			'name' => $name,
			'email' => strtolower($email),
		];
	}

	/**
	 * Add reply to
	 *
	 * @access private
	 * @param string $email
	 * @param string $name
	 */
	public function add_reply_to($email, $name = null) {
		foreach ($this->reply_to as $reply_to) {
			if ($reply_to['email'] == $email) {
				return;
			}
		}

		if ($name !== null) {
			$name = trim($name);
		}

		$this->reply_to[] = [
			'name' => $name,
			'email' => strtolower($email),
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
	 * Add a file to attach giving a filefullname
	 *
	 * @access public
	 * @param $filefullname
	 */
	public function add_attachment_file($filefullname) {
		$this->files[] = $filefullname;
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
	 * set_envelope_from
	 *
	 * @param string $email
	 * @param string $address
	 */
	public function set_envelope_from($email) {
		$this->envelope_from = [
			'email' => $email
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
	 * Add template path
	 *
	 * @access public
	 * @param string $path
	 * @param string $namespace (optional)
	 * @param bool $prepend (optional)
	 */
	public function add_template_path($path, $namespace = null, $prepend = false) {
		$template_path = [
			'path' => $path,
			'namespace' => $namespace
		];

		if ($prepend) {
			array_unshift($this->template_paths, $template_path);
		} else {
			array_push($this->template_paths, $template_path);
		}
	}

	/**
	 * Add template directory
	 *
	 * @Deprecated: for backwards compatibility
	 *
	 * @access public
	 * @param string $path
	 * @param string $namespace (optional)
	 * @param bool $prepend (optional)
	 */
	public function add_template_directory($directory, $namespace = null, $prepend = false) {
		$this->add_template_path($directory);
	}

	/**
	 * Get template directories
	 *
	 * @Deprecated: for backwards compatibility
	 *
	 * @access public
	 * @return array $template_directories
	 */
	public function get_template_directories() {
		return $this->get_template_paths();
	}

	/**
	 * Get template directories
	 *
	 * @access public
	 * @return array $template_paths
	 */
	public function get_template_paths() {
		return $this->template_paths;
	}

	/**
	 * Add header.
	 *
	 * @access public
	 * @param string $key
	 * @param string $value
	 */
	public function add_header(string $key, string $value) {
		$this->headers[$key] = $value;
	}

	/**
	 * Get headers.
	 *
	 * @access public
	 * @return array $headers
	 */
	public function get_headers() {
		if (empty(\Skeleton\Email\Config::$email_type_header) === false) {
			/**
			 * @Deprecated: for backwards compatibility
			 */
			$this->add_header(\Skeleton\Email\Config::$email_type_header, $this->type);
		}

		return $this->headers;
	}

	/**
	 * Build email
	 *
	 * @access public
	 */
	public function build() {
		/**
		 * @Deprecated: for backwards compatibility
		 */
		if (!isset(Config::$email_path) and isset(Config::$email_directory)) {
			Config::$email_path = Config::$email_directory;
		}

		$errors = [];
		if (!$this->validate($errors)) {
			throw new \Exception('Cannot send email, Mail not validated. Errored fields: ' . implode(', ', $errors));
		}

		// archive mailbox ?
		if (Config::$archive_mailbox !== null) {
			$this->add_bcc(Config::$archive_mailbox);
		}

		// preparing template
		$template = new \Skeleton\Template\Template();
		if ($this->translation !== null) {
			$template->set_translation($this->translation);
		}

		if (count($this->template_paths) == 0) {
			$this->add_template_path(Config::$email_path . '/template/');
		}

		foreach ($this->template_paths as $template_paths) {
			$template->add_template_path($template_paths['path'], $template_paths['namespace']);
		}

		// assigning objects to template
		foreach ($this->assigns as $key => $value) {
			$template->assign($key, $value);
		}

		// building Email message
		$message = new \Symfony\Component\Mime\Email();

		try {
			$message->html($template->render( $this->type . '/html.twig'));
			$message->text($template->render( $this->type . '/text.twig'));
		} catch (\Skeleton\Template\Exception\Loader $e) {}

		// set subject
		$message->subject(trim($template->render( $this->type . '/subject.twig' )));

		// Add headers
		$headers = $message->getHeaders();
		foreach ($this->get_headers() as $header_key => $header_value) {
			$headers->addTextHeader($header_key, $header_value);
		}

		// Set sender
		if (isset($this->sender['name'])) {
			$message->addFrom(new \Symfony\Component\Mime\Address($this->sender['email'], $this->sender['name']));
		} else {
			$message->addFrom($this->sender['email']);
		}

		if (isset($this->envelope_from['email'])) {
			$envelope =  new \Symfony\Component\Mailer\Envelope(
				new \Symfony\Component\Mime\Address($this->envelope_from['email']),
				[
					new \Symfony\Component\Mime\Address($this->envelope_from['email']),
				]
			);
		}

		// Set reply to
		foreach ($this->reply_to as $reply_to) {
			if (isset($reply_to['name'])) {
				$message->addReplyTo(new \Symfony\Component\Mime\Address($reply_to['email'], $reply_to['name']));
			} else {
				$message->addReplyTo($reply_to['email']);
			}
		}

		// cleanup duplicates in recipients
		$this->cleanup_recipients();

		// Add recipients
		foreach ($this->recipients as $type => $recipients) {
			$addresses = [];

			foreach ($recipients as $recipient) {
				if (!empty(\Skeleton\Email\Config::$redirect_all_mailbox)) {
					$recipient['email'] = \Skeleton\Email\Config::$redirect_all_mailbox;
				}

				$validator = new \Egulias\EmailValidator\EmailValidator();
				$multipleValidations = new \Egulias\EmailValidator\Validation\MultipleValidationWithAnd([
					new \Egulias\EmailValidator\Validation\RFCValidation(),
				]);

				if (!$validator->isValid($recipient['email'], $multipleValidations)) {
					if (Config::$strict_address_validation !== false) {
						throw new Exception\Validation('Invalid e-mail address: ' . $recipient['email']);
					} else {
						continue;
					}
				}

				if ($recipient['name'] != '') {
					$addresses[] = new \Symfony\Component\Mime\Address($recipient['email'], $recipient['name']);
				} else {
					$addresses[] = $recipient['email'];
				}
			}

			$set_to = 'add' . ucfirst($type);

			foreach ($addresses as $address) {
				try {
					call_user_func([$message, $set_to], $address);
				} catch (\Symfony\Component\Mime\Exception\RfcComplianceException $e) {
					if (Config::$strict_address_validation !== false) {
						throw new Exception\Validation($e->getMessage());
					}
				}
			}
		}

		// attachments
		$this->add_html_images($message);
		$this->attach_files($message);

		unset($template);

		if (isset($envelope)) {
			return [ 'message' => $message, 'envelope' => $envelope ];
		} else {
			return [ 'message' => $message ];
		}
	}

	/**
	 * Send email
	 *
	 * @access public
	 */
	public function send() {

		// creating transport
		$transport = $this->create_transport();

		// preparing mailer
		$mailer = new \Symfony\Component\Mailer\Mailer($transport);

		// build mail message and envelope
		$mail = $this->build();
		$message = $mail['message'];
		if (isset($mail['envelope'])) {
			$envelope = $mail['envelope'];
		}

		// If we have no recipients in the message, and strict validation is
		// disabled, and we do have recipients locally, fail silently. This means
		// all recipients set by the user were invalid.
		if (
			(count($message->getTo()) < 1 and count($message->getCc()) < 1 and count($message->getBcc()) < 1) and
			(isset($this->recipients) and count($this->recipients) > 0) and
			Config::$strict_address_validation === false
			) {
				return;
			}

		// send the email
		if (isset($envelope)) {
			$mailer->send($message, $envelope);
		} else {
			$mailer->send($message);
		}
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
	 * @param \Symfony\Component\Mime\Email $message
	 */
	protected function add_html_images(&$message) {
		/**
		 * @Deprecated: for backwards compatibility
		 */
		if (!isset(Config::$email_path) and isset(Config::$email_directory)) {
			Config::$email_path = Config::$email_directory;
		}
		$path = Config::$email_path . '/media/';
		if (!file_exists($path)) {
			return;
		}

		$html_body = $message->getHtmlBody();

		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if (substr($file,0,1) != '.' && strpos($html_body, $file) !== false) {
					$message->embedFromPath($path . $file, $file);
					$html_body = str_replace($file, "cid:" . $file, $html_body);
				}
			}
		}

		$message->html($html_body);

		closedir($handle);
	}

	/**
	 * Attach files
	 *
	 * @access private
	 * @param \Symfony\Component\Mime\Email $message
	 */
	private function attach_files(&$message) {
		foreach ($this->files as $file) {
			if (gettype($file) == 'string') {
				$message->attachFromPath($file);
			} else {
				$message->attachFromPath($file->get_path(), $file->name);
			}
		}
	}

	/**
	 * Check whether an address exists in the address lists
	 *
	 * @param string $addressee
	 * @param array $lists Array of lists to check (to, cc, bcc)
	 * @return bool
	 */
	private function addressee_exists(string $addressee, array $lists = []): bool {
		foreach ($this->recipients as $recipient_list => $recipients) {
			if (in_array($recipient_list, $lists) or count($lists) === 0) {
				foreach ($recipients as $recipient) {
					if ($recipient['email'] == $addressee) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * create transport
	 *
	 * @access private
	 * @return \Symfony\Component\Mailer\Transport
	 */
	private function create_transport() {
		// setting-up transport
		if (Config::$transport_type == 'smtp') {
			$settings = Config::$transport_smtp_config;
			if (
				isset($settings['host']) === false
				|| isset($settings['port']) === false
			) {
				throw new \Exception('Not all smtp settings are provided');
			}

			$encryption = null;
			if (isset($settings['encryption']) && in_array($settings['encryption'], ['ssl', 'tls'])) {
				$encryption = $settings['encryption'];
			}

			$socket_stream = new \Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream();
			$host = 'localhost';
			if (isset($settings['host'])) {
				$host = $settings['host'];
			}
			$port = 25;
			if (isset($settings['port'])) {
				$port = $settings['port'];
			}
			$transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport($host, $port);

			if (isset($settings['username']) && $settings['password']) {
				$transport->setUsername($settings['username']);
				$transport->setPassword($settings['password']);
			}
		} else {
			// We use a local fork of the Sendmail transport
			// See Transport\Sendmail for details
			$transport = new Transport\Sendmail(Config::$transport_sendmail_command);
		}

		return $transport;
	}

	/**
	 * cleanup recipients
	 *
	 * @access private
	 */
	private function cleanup_recipients() {
		// Remove duplicate recipients, in order of importance
		$types = ['bcc', 'cc', 'to'];
		foreach ($types as $type) {
			array_shift($types);

			if (count($types) === 0) {
				continue;
			}

			if (isset($this->recipients[$type])) {
				foreach ($this->recipients[$type] as $key => $recipient) {
					if ($this->addressee_exists($recipient['email'], $types)) {
						unset($this->recipients[$type][$key]);
					}
				}
			}
		}
	}
}
