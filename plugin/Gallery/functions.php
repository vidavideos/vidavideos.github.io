<?php

function showThis($who) {
    if (empty($_GET['showOnly'])) {
        return true;
    }
    if ($_GET['showOnly'] === $who) {
        return true;
    }
    return false;
}

function createGallery($title, $sort, $rowCount, $getName, $mostWord, $lessWord, $orderString, $defaultSort = "ASC") {
    if (!showThis($getName)) {
        return "";
    }
    $getName = str_replace(array("'",'"',"&quot;","&#039;"), array('','','',''), xss_esc($getName));
    if (!empty($_GET['showOnly'])) {
        $rowCount = 60;
    }
    global $global, $args, $url;
    $paggingId = uniqid();
    ?>
    <div class="clear clearfix">
        <h3 class="galleryTitle">
            <a class="btn-default" href="<?php echo $global['webSiteRootURL']; ?>?showOnly=<?php echo $getName; ?>">
                <i class="glyphicon glyphicon-list-alt"></i>
                <?php
                if (empty($_GET[$getName])) {
                    $_GET[$getName] = $defaultSort;
                }
                if (!empty($orderString)) {
                    $info = createOrderInfo($getName, $mostWord, $lessWord, $orderString);
                    echo "{$title} (" . $info[2] . ") (Page " . $_GET['page'] . ") <a href='" . $info[0] . "' >" . $info[1] . "</a>";
                } else {
                    echo "{$title}";
                }
                ?>
            </a>
        </h3>
        <?php
        $countCols = 0;
        unset($_POST['sort']);
        $_POST['sort'][$sort] = $_GET[$getName];
        $_POST['current'] = $_GET['page'];
        $_POST['rowCount'] = $rowCount;

        $total = Video::getTotalVideos("viewable");
        $totalPages = ceil($total / $_POST['rowCount']);
        $page = $_GET['page'];
        if ($totalPages < $_GET['page']) {
            $page = $totalPages;
            $_POST['current'] = $totalPages;
        }
        $videos = Video::getAllVideos("viewable");
        // need to add dechex because some times it return an negative value and make it fails on javascript playlists
        createGallerySection($videos, dechex(crc32($getName)));
        ?>
        <div class="col-sm-12" style="z-index: 1;">
            <ul id="<?php echo $paggingId; ?>">
            </ul>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('#<?php echo $paggingId; ?>').bootpag({
                total: <?php echo $totalPages; ?>,
                page: <?php echo $page; ?>,
                maxVisible: 10
            }).on('page', function (event, num) {
    <?php echo 'var args = "' . $args . '";'; ?>
                window.location.replace("<?php echo $url; ?>" + num + args);
            });
        });
    </script>
    <?php
}

function createOrderInfo($getName, $mostWord, $lessWord, $orderString) {
    $upDown = "";
    $mostLess = "";
    $tmpOrderString = $orderString;
    if ($_GET[$getName] == "DESC") {
        if (strpos($orderString, $getName . "=DESC")) {
            $tmpOrderString = substr($orderString, 0, strpos($orderString, $getName . "=DESC")) . $getName . "=ASC" . substr($orderString, strpos($orderString, $getName . "=DESC") + strlen($getName . "=DESC"), strlen($orderString));
        } else {
            $tmpOrderString .= $getName . "=ASC";
        }

        $upDown = "<span class='glyphicon glyphicon-arrow-up' >" . __("Up") . "</span>";
        $mostLess = $mostWord;
    } else {
        if (strpos($orderString, $getName . "=ASC")) {
            $tmpOrderString = substr($orderString, 0, strpos($orderString, $getName . "=ASC")) . $getName . "=DESC" . substr($orderString, strpos($orderString, $getName . "=ASC") + strlen($getName . "=ASC"), strlen($orderString));
        } else {
            $tmpOrderString .= $getName . "=DESC";
        }

        $upDown = "<span class='glyphicon glyphicon-arrow-down'>" . __("Down") . "</span>";
        $mostLess = $lessWord;
    }

    if (substr($tmpOrderString, strlen($tmpOrderString) - 1, strlen($tmpOrderString)) == "&") {
        $tmpOrderString = substr($tmpOrderString, 0, strlen($tmpOrderString) - 1);
    }

    return array($tmpOrderString, $upDown, $mostLess);
}

function createGallerySection($videos, $crc = "", $get = array()) {
    global $global, $config, $obj, $advancedCustom;
    $countCols = 0;
    $obj = YouPHPTubePlugin::getObjectData("Gallery");

    foreach ($videos as $value) {

        // that meas auto generate the channelName
        if (empty($get) && !empty($obj->filterUserChannel)) {
            $getCN = array('channelName' => $value['channelName'], 'catName' => @$_GET['catName']);
        }else{
            $getCN = $get;
        }

        $img_portrait = ($value['rotation'] === "90" || $value['rotation'] === "270") ? "img-portrait" : "";
        $name = User::getNameIdentificationById($value['users_id']);
        // make a row each 6 cols
        if ($countCols % 6 === 0) {
            echo '</div><div class="row aligned-row ">';
        }

        $countCols ++;
        ?>
        <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6 galleryVideo thumbsImage fixPadding" style="z-index: 2; min-height: 175px;">
            <a class="galleryLink" videos_id="<?php echo $value['id']; ?>" href="<?php echo Video::getLink($value['id'], $value['clean_title'], false, $getCN); ?>" title="<?php echo $value['title']; ?>">
                <?php
                $images = Video::getImageFromFilename($value['filename'], $value['type']);
                $imgGif = $images->thumbsGif;
                $poster = $images->thumbsJpg;
                ?>
                <div class="aspectRatio16_9">
                    <img src="<?php echo $images->thumbsJpgSmall; ?>" data-src="<?php echo $poster; ?>" alt="<?php echo $value['title']; ?>" class="thumbsJPG img img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $value['rotation']; ?>  <?php echo ($poster != $images->thumbsJpgSmall) ? "blur" : ""; ?>" id="thumbsJPG<?php echo $value['id']; ?>" />
                    <?php if (!empty($imgGif)) { ?>
                        <img src="<?php echo $global['webSiteRootURL']; ?>img/loading-gif.png" data-src="<?php echo $imgGif; ?>" style="position: absolute; top: 0; display: none;" alt="<?php echo $value['title']; ?>" id="thumbsGIF<?php echo $value['id']; ?>" class="thumbsGIF img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $value['rotation']; ?>" height="130" />
                    <?php } ?>
                </div>
                <span class="duration"><?php echo Video::getCleanDuration($value['duration']); ?></span>
            </a>
            <a class="h6 galleryLink" videos_id="<?php echo $value['id']; ?>" href="<?php echo Video::getLink($value['id'], $value['clean_title'], false, $getCN); ?>" title="<?php echo $value['title']; ?>">
                <h2><?php echo $value['title']; ?></h2>
            </a>

            <div class="text-muted galeryDetails">
                <div>
                    <?php if (empty($_GET['catName'])) { ?>
                        <a class="label label-default" href="<?php echo $global['webSiteRootURL']; ?>cat/<?php echo $value['clean_category']; ?>/">
                            <?php
                            if (!empty($value['iconClass'])) {
                                ?>
                                <i class="<?php echo $value['iconClass']; ?>"></i>
                                <?php
                            }
                            ?>
                            <?php echo $value['category']; ?>
                        </a>
                    <?php } ?>
                    <?php
                    if (!empty($obj->showTags)) {
                        $value['tags'] = Video::getTags($value['id']);
                        foreach ($value['tags'] as $value2) {
                            if (!empty($value2->label) && $value2->label === __("Group")) {
                                ?><span class="label label-<?php echo $value2->type; ?>"><?php echo $value2->text; ?></span><?php
                            }
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
                    <?php echo humanTiming(strtotime($value['videoCreation'])), " ", __('ago'); ?>
                </div>
                <div>
                    <i class="fa fa-user"></i>
                    <a class="text-muted" href="<?php echo User::getChannelLink($value['users_id']); ?>/">
                        <?php echo $name; ?>
                    </a>
                    <?php if ((!empty($value['description'])) && !empty($obj->Description)) { ?>
                        <button type="button" data-trigger="focus" class="label label-danger" data-toggle="popover" data-placement="top" data-html="true" title="<?php echo $value['title']; ?>" data-content="<div> <?php echo str_replace('"', '&quot;', nl2br(textToLink($value['description']))); ?> </div>" ><?php echo __("Description"); ?></button>
                    <?php } ?>
                </div>
                <?php if (Video::canEdit($value['id'])) { ?>
                    <div>
                        <a href="<?php echo $global['webSiteRootURL']; ?>mvideos?video_id=<?php echo $value['id']; ?>" class="text-primary">
                            <i class="fa fa-edit"></i> <?php echo __("Edit Video"); ?>
                        </a>
                    </div>
                <?php }
                ?>
                <div class="">
                    <?php if ((empty($_POST['disableAddTo'])) && (( ($advancedCustom != false) && ($advancedCustom->disableShareAndPlaylist == false)) || ($advancedCustom == false))) { ?>
                        <a href="#" class="text-primary" style="float:right;" id="addBtn<?php echo $value['id'] . $crc; ?>" data-placement="top" onclick="loadPlayLists('<?php echo $value['id'] . $crc; ?>', '<?php echo $value['id']; ?>');">
                            <span class="fa fa-plus"></span> <?php echo __("Add to"); ?>
                        </a>
                        <div class="webui-popover-content" >
                            <?php if (User::isLogged()) { ?>
                                <form role="form">
                                    <div class="form-group">
                                        <input class="form-control" id="searchinput<?php echo $value['id'] . $crc; ?>" type="search" placeholder="<?php echo __("Search"); ?>..." />
                                    </div>
                                    <div id="searchlist<?php echo $value['id'] . $crc; ?>" class="list-group">
                                    </div>
                                </form>
                                <div>
                                    <hr>
                                    <div class="form-group">
                                        <input id="playListName<?php echo $value['id'] . $crc; ?>" class="form-control" placeholder="<?php echo __("Create a New Play List"); ?>"  >
                                    </div>
                                    <div class="form-group">
                                        <?php echo __("Make it public"); ?>
                                        <div class="material-switch pull-right">
                                            <input id="publicPlayList<?php echo $value['id'] . $crc; ?>" name="publicPlayList" type="checkbox" checked="checked"/>
                                            <label for="publicPlayList" class="label-success"></label>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-success btn-block" id="addPlayList<?php echo $value['id'] . $crc; ?>" ><?php echo __("Create a New Play List"); ?></button>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <h5><?php echo __("Want to watch this again later?"); ?></h5>
                                <?php echo __("Sign in to add this video to a playlist."); ?>
                                <a href="<?php echo $global['webSiteRootURL']; ?>user" class="btn btn-primary">
                                    <span class="fas fa-sign-in-alt"></span>
                                    <?php echo __("Login"); ?>
                                </a>
                            <?php } ?>
                        </div>
                        <script>
                            $(document).ready(function () {
                                loadPlayLists('<?php echo $value['id'] . $crc; ?>', '<?php echo $value['id']; ?>');
                                $('#addBtn<?php echo $value['id'] . $crc; ?>').webuiPopover();
                                $('#addPlayList<?php echo $value['id'] . $crc; ?>').click(function () {
                                    modal.showPleaseWait();
                                    $.ajax({
                                        url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistAddNew.json.php',
                                        method: 'POST',
                                        data: {
                                            'videos_id': <?php echo $value['id']; ?>,
                                            'status': $('#publicPlayList<?php echo $value['id'] . $crc; ?>').is(":checked") ? "public" : "private",
                                            'name': $('#playListName<?php echo $value['id'] . $crc; ?>').val()
                                        },
                                        success: function (response) {
                                            if (response.status === "1") {
                                                playList = [];
                                                console.log(1);
                                                reloadPlayLists();
                                                loadPlayLists('<?php echo $value['id'] . $crc; ?>', '<?php echo $value['id']; ?>');
                                                //$('#searchlist<?php echo $value['id'] . $crc; ?>').btsListFilter('#searchinput<?php echo $value['id'] . $name; ?>', {itemChild: 'span'});
                                                $('#playListName<?php echo $value['id'] . $crc; ?>').val("");
                                                $('#publicPlayList<?php echo $value['id'] . $crc; ?>').prop('checked', true);
                                            }
                                            modal.hidePleaseWait();
                                        }
                                    });
                                    return false;
                                });
                            });
                        </script>
                    <?php } ?>
                </div>
                <?php
                if ($config->getAllow_download()) {
                    ?>

                <div style="position: relative; overflow: visible; z-index: 3;" class="dropup">
                        <button type="button" class="btn btn-default btn-sm btn-xs"  data-toggle="dropdown">
                            <i class="fa fa-download"></i> <?php echo!empty($advancedCustom->uploadButtonDropdownText) ? $advancedCustom->uploadButtonDropdownText : ""; ?> <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">
                            <?php
                            $files = getVideosURL($value['filename']);
                            //var_dump($files);exit;
                            foreach ($files as $key => $theLink) {
                                if ($theLink['type'] !== 'video') {
                                    continue;
                                }
                                $path_parts = pathinfo($theLink['filename']);
                                ?>
                                <li>
                                    <a href="<?php echo $theLink['url']; ?>?download=1&title=<?php echo urlencode($value['title'] . "_{$key}_.{$path_parts['extension']}"); ?>">
                                        <?php echo __("Download"); ?> <?php echo $key; ?>
                                    </a>
                                </li>
                            <?php }
                            ?>
                        </ul>
                    </div>
                    <?php
                }
                ?>


            </div>
        </div>
    <?php } ?>

    <?php
    unset($_POST['disableAddTo']);
}

function createChannelItem($users_id, $photoURL = "", $identification = "", $rowCount = 12) {
    $total = Video::getTotalVideos("viewable", $users_id);
    if (empty($total)) {
        return false;
    }
    if (empty($photoURL)) {
        $photoURL = User::getPhoto($users_id);
    }
    if (empty($identification)) {
        $identification = User::getNameIdentificationById($users_id);
    }
    ?>
    <div class="clear clearfix">
        <h3 class="galleryTitle">
            <img src="<?php
            echo $photoURL;
            ?>" class="img img-circle img-responsive pull-left" style="max-height: 20px;">
            <span style="margin: 0 5px;">
                <?php
                echo $identification;
                ?>
            </span>
            <a class="btn btn-xs btn-default" href="<?php echo User::getChannelLink($users_id); ?>" style="margin: 0 10px;">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <?php
            echo Subscribe::getButton($users_id);
            ?>
        </h3>
        <div class="row">
            <?php
            $countCols = 0;
            unset($_POST['sort']);
            $_POST['sort']['created'] = "DESC";
            $_POST['current'] = 1;
            $_POST['rowCount'] = $rowCount;
            $videos = Video::getAllVideos("viewable", $users_id);
            createGallerySection($videos);
            ?>
        </div>
    </div>
    <?php
}

$search = "";
$searchPhrase = "";

function clearSearch() {
    global $search, $searchPhrase;
    $search = $_GET['search'];
    $searchPhrase = $_POST['searchPhrase'];
    unset($_GET['search']);
    unset($_POST['searchPhrase']);
}

function reloadSearch() {
    global $search, $searchPhrase;
    $_GET['search'] = $search;
    $_POST['searchPhrase'] = $searchPhrase;
}
?>
