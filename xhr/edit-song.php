<?php
runPlugin('PreSongEdit', $_REQUEST);
if (IS_LOGGED == false) {
    $data = [
        "status" => 400,
        "error" => "Not logged in",
    ];
    echo json_encode($data);
    exit();
} elseif (empty($_POST["song-id"])) {
    $data = [
        "status" => 400,
        "error" => "Undefined song",
    ];
    echo json_encode($data);
    exit();
} elseif (
    empty(
        $db
            ->where("audio_id", secure($_POST["song-id"]))
            ->getValue(T_SONGS, "count(*)")
    )
) {
    $data = [
        "status" => 400,
        "error" => "Undefined song",
    ];
    echo json_encode($data);
    exit();
} else {
    $songData = $db
        ->where("audio_id", secure($_POST["song-id"]))
        ->getOne(T_SONGS);
    $request = [];
    $request[] = empty($_POST["title"]) || empty($_POST["description"]);
    $request[] = empty($_POST["tags"]);
    if (in_array(true, $request)) {
        $error = lang("Please check your details");
    } else {
        $request = [];
        if (!empty($_POST["song-thumbnail"])) {
            $request[] = !in_array(
                $_POST["song-thumbnail"],
                $_SESSION["uploads"]
            );
        }
        if (in_array(true, $request)) {
            $error = lang("Something went wrong Please try again later!");
        }
    }
    if (empty($error)) {
        $thumbnail = $songData->thumbnail;
        if (!empty($_POST["song-thumbnail"])) {
            $thumbnail = secure($_POST["song-thumbnail"], 0);
            if (file_exists($thumbnail)) {
                unlink($songData->thumbnail);
                PT_DeleteFromToS3($songData->thumbnail);
            }
        }
        $category_id = $songData->category_id;
        $convert = true;
        if (
            !empty($_POST["category_id"]) &&
            is_numeric($_POST["category_id"]) &&
            $_POST["category_id"] > 0
        ) {
            $category_id = secure($_POST["category_id"]);
        }
        $link_regex = "/(http\:\/\/|https\:\/\/|www\.)([^\ ]+)/i";
        $i = 0;
        preg_match_all($link_regex, secure($_POST["description"]), $matches);
        foreach ($matches[0] as $match) {
            $match_url = strip_tags($match);
            $syntax = "[a]" . urlencode($match_url) . "[/a]";
            $_POST["description"] = str_replace(
                $match,
                $syntax,
                $_POST["description"]
            );
        }
        $audio_privacy = $songData->availability;
        if (isset($_POST["privacy"])) {
            if (in_array($_POST["privacy"], [0, 1])) {
                $audio_privacy = secure($_POST["privacy"]);
            }
        }
        $age_restriction = $songData->age_restriction;
        if (isset($_POST["age_restriction"])) {
            if (in_array($_POST["age_restriction"], [0, 1])) {
                $age_restriction = secure($_POST["age_restriction"]);
            }
        }
        $song_price = $songData->price;
        if (isset($_POST["song-price"])) {
            if (in_array($_POST["song-price"], $music->song_prices)) {
                $song_price = secure($_POST["song-price"]);
            }
        } else {
            $song_price = 0;
        }

        $spotlight = $songData->spotlight;
        if (!empty($_POST["spotlight"]) && IsAdmin()) {
            if ($_POST["spotlight"] == "yes") {
                $spotlight = 1;
            } elseif ($_POST["spotlight"] == "no") {
                $spotlight = 0;
            }
            if ($spotlight == $songData->spotlight) {
                $spotlight = $songData->spotlight;
            }
        }

        $allow_downloads = $songData->allow_downloads;
        if (isset($_POST["allow_downloads"])) {
            if (in_array($_POST["allow_downloads"], [0, 1])) {
                $allow_downloads = secure($_POST["allow_downloads"]);
            }
        }
        $display_embed = $songData->display_embed;
        if (isset($_POST["display_embed"])) {
            if (in_array($_POST["display_embed"], [0, 1])) {
                $display_embed = secure($_POST["display_embed"]);
            }
        }
        $itunes_token = "";
        if (
            !empty($_POST["itunes_token"]) &&
            $music->config->itunes_import == "on" &&
            $music->config->can_use_itunes_affiliate &&
            strpos($songData->src, "TUNES")
        ) {
            $itunes_token = secure($_POST["itunes_token"]);
        }

        $data_insert = [
            "title" => secure($_POST["title"]),
            "description" => secure($_POST["description"]),
            "lyrics" => secure($_POST["lyrics"]),
            "tags" => secure(str_replace("#", "", $_POST["tags"])),
            "category_id" => $category_id,
            "thumbnail" => $thumbnail,
            "availability" => $audio_privacy,
            "age_restriction" => $age_restriction,
            "price" => $song_price,
            "spotlight" => $spotlight,
            "allow_downloads" => $allow_downloads,
            "display_embed" => $display_embed,
            "itunes_token" => $itunes_token,
        ];
        if (!empty($_POST["song-thumbnail"])) {
            PT_UploadToS3($thumbnail);
        }
        if ($songData->user_id == $user->id || isAdmin()) {
            $update = $db
                ->where("id", $songData->id)
                ->update(T_SONGS, $data_insert);
            if ($update) {
                runPlugin('AfterSongEdited', $data_insert);
                $data = [
                    "status" => 200,
                    "link" => getLink("track/$songData->audio_id"),
                ];
            }
        }
    } else {
        $data = [
            "status" => 400,
            "message" => $error,
        ];
    }
}