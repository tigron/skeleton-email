<?php
/**
 * Exception Validation class
 *
 * Validation
 *
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Email\Exception;

class Validation extends \Exception {

	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $message
	 */
	public function __construct($message) {
		$this->message = $message;
	}

}
