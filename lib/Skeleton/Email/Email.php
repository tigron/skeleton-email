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
	 * Template directories
	 *
	 * @access private
	 * @var array $template_directories
	 */
	private $template_directories = [];

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

		$this->recipients['bcc'][] = [
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
	 * Add template directory
	 *
	 * @access public
	 * @param string $path
	 * @param string $namespace (optional)
	 * @param bool $prepend (optional)
	 */
	public function add_template_directory($path, $namespace = null, $prepend = false) {
		$template_directory = [
			'directory' => $path,
			'namespace' => $namespace
		];

		if ($prepend) {
			array_unshift($this->template_directories, $template_directory);
		} else {
			array_push($this->template_directories, $template_directory);
		}
	}

	/**
	 * Get template directories
	 *
	 * @access public
	 * @return array $template_directories
	 */
	public function get_template_directories() {
		return $this->template_directories;
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

		if (count($this->template_directories) == 0) {
			$this->add_template_directory(Config::$email_directory . '/template/');
		}

		foreach ($this->template_directories as $template_directory) {
			$template->add_template_directory($template_directory['directory'], $template_directory['namespace']);
		}

		foreach ($this->assigns as $key => $value) {
			$template->assign($key, $value);
		}

		$transport = \Swift_MailTransport::newInstance();
		$mailer = \Swift_Mailer::newInstance($transport);
		$message = \Swift_Message::newInstance()
			->setBody($template->render( $this->type . '/html.twig'), 'text/html')
			->addPart($template->render( $this->type . '/text.twig' ), 'text/plain')
			->setSubject(trim($template->render( $this->type . '/subject.twig' )))
		;

		if (isset(\Skeleton\Email\Config::$email_type_header) AND \Skeleton\Email\Config::$email_type_header !== null) {
			$headers = $message->getHeaders();
			$headers->addTextHeader(\Skeleton\Email\Config::$email_type_header, $this->type);
		}

		if (isset($this->sender['name'])) {
			$message->setFrom([$this->sender['email'] => $this->sender['name']]);
		} else {
			$message->setFrom($this->sender['email']);
		}

		$this->add_html_images($message);
		$this->attach_files($message);

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

		foreach ($this->recipients as $type => $recipients) {
			$addresses = [];

			foreach ($recipients as $recipient) {
				if (isset(\Skeleton\Email\Config::$redirect_all_mailbox) AND \Skeleton\Email\Config::$redirect_all_mailbox !== null) {
					$recipient['email'] = \Skeleton\Email\Config::$redirect_all_mailbox;
				}

				if (!\Swift_Validate::email($recipient['email'])) {
					if (Config::$strict_address_validation !== false) {
						throw new Exception\Validation('Invalid e-mail address: ' . $recipient['email']);
					} else {
						continue;
					}
				}

				if ($recipient['name'] != '') {
					$addresses[$recipient['email']] = $recipient['name'];
				} else {
					$addresses[] = $recipient['email'];
				}
			}

			$set_to = 'set' . ucfirst($type);

			try {
				call_user_func([$message, $set_to], $addresses);
			} catch (\Swift_RfcComplianceException $e) {
				if (Config::$strict_address_validation !== false) {
					throw new Exception\Validation($e->getMessage());
				}
			}
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
			if (gettype($file) == 'string') {
				$message->attach(\Swift_Attachment::fromPath($file)->setFilename(basename($file)));
			} else {
				$message->attach(\Swift_Attachment::fromPath($file->get_path())->setFilename($file->name));
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
}
