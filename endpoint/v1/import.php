<?php
if (empty($_POST['track_link']) || IS_LOGGED == false) {
    $data = [
        'status' => 400,
        'error' => 'track_link can not be empty'
    ];
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
if (strpos($_POST['track_link'], 'soundcloud.com') === false && strpos($_POST['track_link'], 'music.apple.com') === false && strpos($_POST['track_link'], 'deezer.com') === false && !preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $_POST['track_link'], $match)) {
    $data['status'] = 400;
    $data['error'] = 'Please enter a valid link to import.';
    header('Content-type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}
if ($music->config->soundcloud_import == 'off' && strpos($_POST['track_link'], 'soundcloud.com')) {
    $data['status'] = 400;
    $data['error'] = 'Please enter a valid link to import.';
    header('Content-type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}
if ($music->config->itunes_import == 'off' && strpos($_POST['track_link'], 'music.apple.com')) {
    $data['status'] = 400;
    $data['error'] = 'Please enter a valid link to import.';
    header('Content-type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}
if ($music->config->deezer_import == 'off' && strpos($_POST['track_link'], 'deezer.com')) {
    $data['status'] = 400;
    $data['error'] = 'Please enter a valid link to import.';
    header('Content-type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}
if (strpos($_POST['track_link'], 'music.apple.com') && !strpos($_POST['track_link'], 'i=')) {
    $data['status'] = 400;
    $data['error'] = 'Link must be like EX: https://music.apple.com/us/album/wolves/1445055015?i=1445055017';
    header('Content-type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit();
}
$path = parse_url($_POST['track_link'])['path'];
if (strpos($_POST['track_link'], 'deezer.com') && !empty($path) && strpos($path, 'track')) {
    $array = explode('/', $path);
    if (empty($array[count($array) - 1])) {
        $data['status'] = 400;
        $data['error'] = 'Link must be like EX: https://www.deezer.com/track/926219142';
        header('Content-type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit();
    }
}
$track_link = secure($_POST['track_link']);
$track = ImportFormSoundCloud($track_link);
if( isset($track['audio_id']) ){
    RecordUserActivities('import',array('audio_id' => $track['audio_id']));
    if (!empty($track['id'])) {
        $create_activity = createActivity([
            'user_id' => $music->user->id,
            'type' => 'imported_track',
            'track_id' => $track['id'],
        ]);
    }
    $data['status'] = 200;
    $data['trackid'] = $track['audio_id'];
}else if( isset($track['duplicated']) ){
    $data['status'] = 400;
    $data['error'] = 'You can not import this track because this track is imported before.';
}else if( isset($track['soundcloud_pro']) ){
    $data['status'] = 400;
    $data['error'] = 'You can not import this track because this track is one of SoundCloud Go+ tracks.';
}else{
    $data['status'] = 400;
    $data['error'] = 'Error found while importing your track, please check soundcloud client ID.';
}














// if (!empty($_POST['track_link'])) {
// 	if (strpos($_POST['track_link'], 'soundcloud.com') === false && strpos($_POST['track_link'], 'music.apple.com') === false && strpos($_POST['track_link'], 'deezer.com') === false && !preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $_POST['track_link'], $match)) {
// 	    $data['status'] = 400;
// 	    $data['error'] = 'Please enter a valid link to import.';
// 	}
// 	else{
// 		if ($music->config->soundcloud_import == 'off' && strpos($_POST['track_link'], 'soundcloud.com')) {
// 		    $data['status'] = 400;
// 		    $data['error'] = 'Please enter a valid link to import. OR soundcloud_import is disabled';
// 		}
// 		else{
// 			if ($music->config->itunes_import == 'off' && strpos($_POST['track_link'], 'music.apple.com')) {
// 			    $data['status'] = 400;
// 			    $data['error'] = 'Please enter a valid link to import. OR itunes_import is disabled';
// 			}
// 			else{
// 				if ($music->config->deezer_import == 'off' && strpos($_POST['track_link'], 'deezer.com')) {
// 				    $data['status'] = 400;
// 				    $data['error'] = 'Please enter a valid link to import. OR deezer_import is disabled';
// 				}
// 				else{
// 					if (strpos($_POST['track_link'], 'music.apple.com') && !strpos($_POST['track_link'], 'i=')) {
// 					    $data['status'] = 400;
// 					    $data['error'] = 'Link must be like EX: https://music.apple.com/us/album/wolves/1445055015?i=1445055017';
// 					}
// 					else{
// 						$path = parse_url($_POST['track_link'])['path'];
// 						$array = explode('/', $path);
// 						if (strpos($_POST['track_link'], 'deezer.com') && !empty($path) && strpos($path, 'track') && empty($array[count($array) - 1])) {
// 					        $data['status'] = 400;
// 					        $data['error'] = 'Link must be like EX: https://www.deezer.com/track/926219142';
// 						}
// 						else{
// 							$track_link = secure($_POST['track_link']);
// 							$track = ImportFormSoundCloud($track_link);
// 							if( isset($track['audio_id']) ){
// 							    $data['status'] = 200;
// 							    $getIDAudio = $db->where('audio_id', $track['audio_id'])->getValue(T_SONGS, 'id');
// 							    $userSong = songData($getIDAudio);
// 					            unset($userSong->publisher->password);
// 					            foreach ($userSong->songArray as $key => $value) {
// 					                unset($userSong->songArray->{$key}->USER_DATA->password);
// 					            }
// 					            $data['data'] = $userSong;
// 							}else if( isset($track['duplicated']) ){
// 							    $data['status'] = 400;
// 							    $data['error'] = 'You can not import this track because this track is imported before.';
// 							}else if( isset($track['soundcloud_pro']) ){
// 							    $data['status'] = 400;
// 							    $data['error'] = 'You can not import this track because this track is one of SoundCloud Go+ tracks.';
// 							}else{
// 							    $data['status'] = 400;
// 							    $data['error'] = 'Error found while importing your track, please check soundcloud client ID.';
// 							}
// 						}
// 					}
// 				}
// 			}
// 		}
// 	}
// }
// else{
// 	$data = [
//         'status' => 400,
//         'error' => 'track_link can not be empty'
//     ];
// }