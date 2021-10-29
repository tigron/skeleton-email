<?php
/**
 * Config class
 * Configuration for Skeleton\Email
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Email;

class Config {

	/**
	 * The email directory
	 *
	 * @access public
	 * @var string $email_directory
	 */
	public static $email_directory = null;

	/**
	 * Archive mailbox
	 *
	 * @access public
	 * @var string $archive_mailbox
	 */
	public static $archive_mailbox = null;

	/**
	 * Redirect all emails to this mailbox
	 *
	 * @access public
	 * @var string $redirect_all_mailbox
	 */
	public static $redirect_all_mailbox = null;

	/**
	 * Name of the header in which we will print the e-mail type
	 * Defaults to null, which will disable the header inlcusion.
	 *
	 * @access public
	 * @var string $email_type_header
	 */
	public static $email_type_header = null;

	/**
	 * Strict e-mail address validation is enabled by default. If this is
	 * disabled, sending the message will silently fail for a given recipient
	 * if the e-mail address provided is invalid.
	 *
	 * @access public
	 * @var string $strict_address_validation
	 */
	public static $strict_address_validation = true;

	/**
	 * The transport type service to use (sendmail or smtp)
	 * Defaults to sendmail
	 *
	 * @access public
	 * @var string $transport_type
	 */
	public static $transport_type = 'sendmail';

	/**
	 * Config needed to connect to a smtp server
	 * Required options: host, port
	 * Optional options: encryption (tls or ssl), username, password
	 *
	 * @access public
	 * @var array $transport_type_smtp_config
	 */
	public static $transport_smtp_config = [ 'host' => null, 'port' => 25 ];

	/**
	 * A default sendmail command to adjust to your setup if needed
	 *
	 * @access public
	 * @var string $transport_sendmail_command
	 */
	public static $transport_sendmail_command = '/usr/sbin/sendmail -t -i';
}
