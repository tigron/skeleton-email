# skeleton-email

## Description

This library contains the email functionality of Skeleton
## Installation

Installation via composer:

    composer require tigron/skeleton-email

## Howto

Set the directory for emails

    \Skeleton\Email\Config::$email_directory = $some_very_cool_directory;

The email directory must have the following structure

    - media
        - sample_media_dir1
            - image1.png
            - image2.png
        - sample_media_dir2
            - image3.png
        - image4.png
    - template
        - email_type1
            - html.twig
            - subject.twig
            - text.twig
        - email_type2
            - html.twig
            - subject.twig
            - text.twig

Each email type must have its own directory containing the following files:
 - html.twig => the HTML version of the email
 - subject.twig => The subject line of the email
 - text.twig => The text version of the email

References to media in the mail content will be fetches from the media directory


Create a new mail:

    $email = new \Skeleton\Email\Email('email_type1');
    $email->set_sender('sender@example.com', 'Test sender');

    /**
     * Optional: translate the mail with a defined Translation object
     */
    $language = Skeleton\I18n\Language::get_by_id(2);
    $application = 'email'; // Used to fetch the po file
    $translation = Skeleton\I18n\Translation::get($language, $application);
    $mail->set_translation($translation));

    /**
     * Optional: Archive mailbox. Send a copy of every mail to a given mailbox
     */
	\Skeleton\Email\Config::$archive_mailbox = 'my_archive@example.com';

	$email->assign('variable1', 'content1');
    $email->send();
