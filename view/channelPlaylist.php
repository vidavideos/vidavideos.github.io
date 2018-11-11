<?php
global $global, $config, $isChannel;
$isChannel = 1; // still workaround, for gallery-functions, please let it there.
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/video.php';
require_once $global['systemRootPath'] . 'objects/playlist.php';

if (empty($_GET['channelName'])) {
    if (User::isLogged()) {
        $_GET['user_id'] = User::getId();
    } else {
        return false;
    }
} else {
    $user = User::getChannelOwner($_GET['channelName']);
    if (!empty($user)) {
        $_GET['user_id'] = $user['id'];
    } else {
        $_GET['user_id'] = $_GET['channelName'];
    }
}
$user_id = $_GET['user_id'];

$publicOnly = true;
$isMyChannel = false;
if (User::isLogged() && $user_id == User::getId()) {
    $publicOnly = false;
    $isMyChannel = true;
}

$playlists = PlayList::getAllFromUser($user_id, $publicOnly);
?>

<?php
foreach ($playlists as $playlist) {
    $videosArrayId = PlayList::getVideosIdFromPlaylist($playlist['id']);
    $videos = Video::getAllVideos("a", false, false, $videosArrayId);
    $videos = PlayList::sortVideos($videos, $videosArrayId);
    $playListButtons = YouPHPTubePlugin::getPlayListButtons($playlist['id']);
    ?>

    <div class="panel panel-default" playListId="<?php echo $playlist['id']; ?>">
        <div class="panel-heading">

            <strong style="font-size: 1.1em;" class="playlistName"><?php echo $playlist['name']; ?> </strong>

            <?php
            if (!empty($videosArrayId)) {
                ?>
                <a href="<?php echo $global['webSiteRootURL']; ?>playlist/<?php echo $playlist['id']; ?>" class="btn btn-xs btn-default playAll hrefLink" ><span class="fa fa-play"></span> <?php echo __("Play All"); ?></a><?php echo $playListButtons; ?>
                <?php
            }
            if ($isMyChannel) {
                ?>
                <script>
                    $(function () {
                        $("#sortable<?php echo $playlist['id']; ?>").sortable({
                            stop: function (event, ui) {
                                modal.showPleaseWait();
                                saveSortable(this, <?php echo $playlist['id']; ?>);
                            }
                        });
                        $("#sortable<?php echo $playlist['id']; ?>").disableSelection();
                    });
                </script>
                <div class="pull-right btn-group">

                    <?php
                    if (!empty($videosArrayId)) {
                        ?>
                        <button class="btn btn-xs btn-info" ><i class="fa fa-info-circle"></i> <?php echo __("Drag and drop to sort"); ?></button>

                        <?php
                    }
                    ?>
                    <button class="btn btn-xs btn-danger deletePlaylist" playlist_id="<?php echo $playlist['id']; ?>" ><span class="fa fa-trash-o"></span> <?php echo __("Delete"); ?></button>
                    <button class="btn btn-xs btn-primary renamePlaylist" playlist_id="<?php echo $playlist['id']; ?>" ><span class="fa fa-pencil"></span> <?php echo __("Rename"); ?></button>
                </div>
                <?php
            }
            ?>
        </div>

        <?php
        if (!empty($videosArrayId)) {
            ?>

            <div class="panel-body">

                <div id="sortable<?php echo $playlist['id']; ?>" style="list-style: none;">
                    <?php
                    $count = 0;
                    foreach ($videos as $value) {
                        $count++;
                        $img_portrait = ($value['rotation'] === "90" || $value['rotation'] === "270") ? "img-portrait" : "";
                        $name = User::getNameIdentificationById($value['users_id']);

                        $images = Video::getImageFromFilename($value['filename'], $value['type']);
                        $imgGif = $images->thumbsGif;
                        $poster = $images->thumbsJpg;
                        ?>
                        <li class="col-lg-2 col-md-4 col-sm-4 col-xs-6 galleryVideo " id="<?php echo $value['id']; ?>">
                            <div class="panel panel-default" playListId="<?php echo $playlist['id']; ?>">
                                <div class="panel-body" style="overflow: hidden;">

                                    <a class="aspectRatio16_9" href="<?php echo $global['webSiteRootURL']; ?>video/<?php echo $value['clean_title']; ?>" title="<?php echo $value['title']; ?>" style="margin: 0;" >
                                        <img src="<?php echo $poster; ?>" alt="<?php echo $value['title']; ?>" class="img img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $value['rotation']; ?>" />
                                        <span class="duration"><?php echo Video::getCleanDuration($value['duration']); ?></span>
                                    </a>
                                    <a class="hrefLink" href="<?php echo $global['webSiteRootURL']; ?>video/<?php echo $value['clean_title']; ?>" title="<?php echo $value['title']; ?>">
                                        <h2><?php echo $value['title']; ?></h2>
                                    </a>
                                    <div class="text-muted galeryDetails">
                                        <div>
                                            <?php
                                            $value['tags'] = Video::getTags($value['id']);
                                            foreach ($value['tags'] as $value2) {
                                                if ($value2->label === __("Group")) {
                                                    ?>
                                                    <span class="label label-<?php echo $value2->type; ?>"><?php echo $value2->text; ?></span>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div>
                                            <i class="fa fa-eye"></i>
                                            <span itemprop="interactionCount">
                                                <?php echo number_format($value['views_count'], 0); ?> <?php echo __("Views"); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <i class="fa fa-clock-o"></i>
                                            <?php
                                            echo humanTiming(strtotime($value['videoCreation'])), " ", __('ago');
                                            ?>
                                        </div>
                                        <div>
                                            <i class="fa fa-user"></i>
                                            <?php
                                            echo $name;
                                            ?>
                                        </div>
                                        <?php
                                        if (Video::canEdit($value['id'])) {
                                            ?>
                                            <div>
                                                <a href="<?php echo $global['webSiteRootURL']; ?>mvideos?video_id=<?php echo $value['id']; ?>" class="text-primary"><i class="fa fa-edit"></i> <?php echo __("Edit Video"); ?></a>


                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        if ($isMyChannel) {
                                            ?>
                                            <div>
                                                <span style=" cursor: pointer;" class="btn-link text-primary removeVideo" playlist_id="<?php echo $playlist['id']; ?>" video_id="<?php echo $value['id']; ?>">
                                                    <i class="fa fa-trash"></i> <?php echo __("Remove"); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-primary" playlist_id="<?php echo $playlist['id']; ?>" video_id="<?php echo $value['id']; ?>">
                                                    <i class="fas fa-sort-numeric-down"></i> <?php echo __("Sort"); ?> 
                                                    <input type="number" step="1" class="video_order" value="<?php echo intval($playlist['videos'][$count - 1]['video_order']); ?>" style="max-width: 50px;">
                                                    <button class="btn btn-sm btn-xs sortNow"><i class="fas fa-check-square"></i></button>
                                                </span>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <?php
        }
        ?>

    </div>
    <?php
}
?>
<script>
    function saveSortable($sortableObject, playlist_id) {
        var list = $($sortableObject).sortable("toArray");
        $.ajax({
            url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistSort.php',
            data: {
                "list": list,
                "playlist_id": playlist_id
            },
            type: 'post',
            success: function (response) {
                $("#channelPlaylists").load(webSiteRootURL + "view/channelPlaylist.php?channelName=" + channelName);
                modal.hidePleaseWait();
            }
        });
    }

    function sortNow($t, position) {
        var $this = $($t).closest('.galleryVideo');
        var $uiDiv = $($t).closest('.ui-sortable');
        var $playListId = $($t).closest('.panel').attr('playListId');
        var $list = $($t).closest('.ui-sortable').find('li');
        if (position < 0) {
            return false;
        }
        if (position === 0) {
            $this.slideUp(500, function () {
                $this.insertBefore($this.siblings(':eq(0)'));
                saveSortable($uiDiv, $playListId);
            }).slideDown(500);
        } else if ($list.length - 1 > position) {
            $this.slideUp(500, function () {
                $this.insertBefore($this.siblings(':eq(' + position + ')'));
                saveSortable($uiDiv, $playListId);
            }).slideDown(500);
        } else {
            $this.slideUp(500, function () {
                $this.insertAfter($this.siblings(':eq(' + ($list.length - 2) + ')'));
                saveSortable($uiDiv, $playListId);
            }).slideDown(500);
        }
    }

    var currentObject;
    $(function () {
        $('.removeVideo').click(function () {
            currentObject = this;
            swal({
                title: "<?php echo __("Are you sure?"); ?>",
                text: "<?php echo __("You will not be able to recover this action!"); ?>",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "<?php echo __("Yes, delete it!"); ?>",
                closeOnConfirm: true
            },
                    function () {
                        modal.showPleaseWait();
                        var playlist_id = $(currentObject).attr('playlist_id');
                        var video_id = $(currentObject).attr('video_id');
                        $.ajax({
                            url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistRemoveVideo.php',
                            data: {
                                "playlist_id": playlist_id,
                                "video_id": video_id
                            },
                            type: 'post',
                            success: function (response) {
                                reloadPlayLists();
                                $(".playListsIds" + video_id).prop("checked", false);
                                $(currentObject).closest('.galleryVideo').fadeOut();
                                modal.hidePleaseWait();
                            }
                        });
                    });
        });

        $('.deletePlaylist').click(function () {
            currentObject = this;
            swal({
                title: "<?php echo __("Are you sure?"); ?>",
                text: "<?php echo __("You will not be able to recover this action!"); ?>",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "<?php echo __("Yes, delete it!"); ?>",
                closeOnConfirm: true
            },
                    function () {
                        modal.showPleaseWait();
                        var playlist_id = $(currentObject).attr('playlist_id');
                        console.log(playlist_id);
                        $.ajax({
                            url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistRemove.php',
                            data: {
                                "playlist_id": playlist_id
                            },
                            type: 'post',
                            success: function (response) {
                                $(currentObject).closest('.panel').slideUp();
                                modal.hidePleaseWait();
                            }
                        });
                    });

        });

        $('.renamePlaylist').click(function () {
            currentObject = this;
            swal({
                title: "<?php echo __("Change Playlist Name"); ?>!",
                text: "<?php echo __("What is the new name?"); ?>",
                type: "input",
                showCancelButton: true,
                closeOnConfirm: true,
                inputPlaceholder: "<?php echo __("Playlist name?"); ?>"
            },
                    function (inputValue) {
                        if (inputValue === false)
                            return false;

                        if (inputValue === "") {
                            swal.showInputError("<?php echo __("You need to tell us the new name?"); ?>");
                            return false
                        }

                        modal.showPleaseWait();
                        var playlist_id = $(currentObject).attr('playlist_id');
                        console.log(playlist_id);
                        $.ajax({
                            url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistRename.php',
                            data: {
                                "playlist_id": playlist_id,
                                "name": inputValue
                            },
                            type: 'post',
                            success: function (response) {
                                $(currentObject).closest('.panel').find('.playlistName').text(inputValue);
                                modal.hidePleaseWait();
                            }
                        });
                        return false;
                    });

        });

        $('.sortNow').click(function () {
            var $val = $(this).siblings("input").val();
            sortNow(this, $val);
        });

        $('.video_order').keypress(function (e) {
            if (e.which == 13) {
                sortNow(this, $(this).val());
            }
        });

    });
</script>