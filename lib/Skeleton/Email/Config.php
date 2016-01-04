<?php
/**
 * Config class
 * Configuration for Skeleton\File\Picture
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

}
