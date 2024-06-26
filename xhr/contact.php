<?php
if (!empty($_POST)) {
    runPlugin('PreContactMail', $_POST);
    if (
        empty($_POST["name"]) ||
        empty($_POST["email"]) ||
        empty($_POST["message"])
    ) {
        $errors[] = lang("Please check your details");
    } else {
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $errors[] = lang("This e-mail is invalid");
        }
        if (empty($errors)) {
            $name = secure($_POST["name"]);
            $email = secure($_POST["email"]);
            $message = secure($_POST["message"]);

            $send_message_data = [
                "from_email" => $music->config->email,
                "from_name" => $name,
                "reply-to" => $email,
                "to_email" => $music->config->email,
                "to_name" => $music->config->name,
                "subject" => "Contact us new message",
                "charSet" => "utf-8",
                "message_body" => $message,
                "is_html" => false,
            ];

            $send = sendMessage($send_message_data);
            if ($send) {
                runPlugin('AfterContactMail', $send_message_data);
                $data = [
                    "status" => 200,
                    "message" => lang("E-mail sent successfully"),
                ];
            }
        }
    }
}
?>