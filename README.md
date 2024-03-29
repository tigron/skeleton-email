# skeleton-email

## Description

This library contains the email functionality of Skeleton
## Installation

Installation via composer:

    composer require tigron/skeleton-email
    composer require tigron/skeleton-template-twig

## Howto

Set the path for emails

    \Skeleton\Email\Config::$email_path = $some_very_cool_directory;

The email path must have the following structure

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
    $email->add_to('to@example.com' [, 'to name' ]);

    /**
     * Optional: translate the mail with a defined Translation object
     */
    $language = Skeleton\I18n\Language::get_by_id(2);
    $application = 'email'; // Used to fetch the po file
    $translation = Skeleton\I18n\Translation::get($language, $application);
    $email->set_translation($translation);

    /**
     * Optional: add different recipient types
     */
    $email->add_cc('cc@example.com' [, 'cc name' ]);
    $email->add_bcc('bcc@example.com' [, 'bcc name' ]);

    /**
     * Optional: add reply-to recipient
     */
    $email->add_reply_to('reply-to@example.com' [, 'reply-to name' ]);

    /**
     * Optional: attach file(s)
     */
    $email->add_attachment(\Skeleton\File\File::get_by_id(1234));
    $email->add_attachment_file('/some_very_cool_path/filename.ext');

    /**
     * Optional: Archive mailbox. Send a copy of every mail to a given mailbox
     */
    \Skeleton\Email\Config::$archive_mailbox = 'my_archive@example.com';

    /**
     * Optional: Transport type. Send email using smtp or sendmail (default: sendmail)
     * When smtp is used at least a host and port are required.
     * Optionally you can also define which encryption is needed (ssl or tls)
     * and if authentication is required (username, password).
     */
    \Skeleton\Email\Config::$transport_type = 'smtp';
    \Skeleton\Email\Config::$transport_smtp_config = [
        'host' => 'smtp.example.com',
        'port' => 25,
        'encryption' => 'ssl',
        'username' => 'foo',
        'password' => 'bar'
    ];

    /**
     * Optional: Assign variables
     */
    $email->assign('variable1', 'content1');

    /**
     * Send email
     */
    $email->send();
