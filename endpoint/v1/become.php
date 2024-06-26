<?php
if ($music->user->artist == 1) {
	$data['status'] = 400;
	$data['error'] = 'You are already an artist!';
}
elseif (empty($_POST['name']) || empty($_POST['category_id']) || empty($_FILES['passport']) || empty($_FILES['photo'])) {
	$data['status'] = 400;
    $data['error'] = "Please check your details";
}
elseif ($db->where('user_id', $music->user->id)->getValue(T_ARTIST_R, "count(*)") > 0) {
	$data['status'] = 400;
	$data['error'] = 'Your request has been already sent, we will get back to you shourtly.';
}
else{
	$name          = secure($_POST['name']);
    $details       = (!empty($_POST['details'])) ? secure($_POST['details']) : '';
    $category_id =  0;
    if (!empty($_POST['category_id'])) {
        if (in_array($_POST['category_id'], array_keys($categories))) {
            $category_id = secure($_POST['category_id']);
        }
    }
    $website = (!empty($_POST['website'])) ? secure($_POST['website']) : '';
    $create_data = [
        'name' => $name,
        'website' => $website,
        'details' => $details,
        'category_id' => $category_id,
        'user_id' => $music->user->id,
        'time' => time()
    ];
    if (!empty($_FILES['passport']['tmp_name'])) {
        $file_info = array(
            'file' => $_FILES['passport']['tmp_name'],
            'size' => $_FILES['passport']['size'],
            'name' => $_FILES['passport']['name'],
            'type' => $_FILES['passport']['type'],
            'allowed' => 'jpg,png,jpeg,gif,webp'
        );
        $file_upload = shareFile($file_info);
        if (!empty($file_upload['filename'])) {
            $create_data['passport'] = $file_upload['filename'];
        }
    }
    if (!empty($_FILES['photo']['tmp_name'])) {
        $file_info = array(
            'file' => $_FILES['photo']['tmp_name'],
            'size' => $_FILES['photo']['size'],
            'name' => $_FILES['photo']['name'],
            'type' => $_FILES['photo']['type'],
            'allowed' => 'jpg,png,jpeg,gif,webp'
        );
        $file_upload = shareFile($file_info);
        if (!empty($file_upload['filename'])) {
            $create_data['photo'] = $file_upload['filename'];
        }
    }
    if (empty($errors) && !empty($create_data['photo']) && !empty($create_data['passport'])) {
        if (isAdmin() || $music->user->id) {
            $insert = $db->insert(T_ARTIST_R, $create_data);
            if ($insert) {
                $notif_data = array(
                    'recipient_id' => 0,
                    'type' => 'artist',
                    'admin' => 1,
                    'time' => time()
                );
                $db->insert(T_NOTIFICATION,$notif_data);
                $data = [
                    'status' => 200,
                    'message' => 'Your request is under review'
                ];
            }
        }
    } else {
    	$data['status'] = 400;
    	$data['error'] = "Error found while processing your request, please try again later.";
    }
}