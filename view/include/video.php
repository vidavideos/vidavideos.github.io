<?php
$playNowVideo = $video;
$transformation = "{rotate:" . $video['rotation'] . ", zoom: " . $video['zoom'] . "}";

if ($video['rotation'] === "90" || $video['rotation'] === "270") {
    $aspectRatio = "9:16";
    $vjsClass = "vjs-9-16";
    $embedResponsiveClass = "embed-responsive-9by16";
} else {
    $aspectRatio = "16:9";
    $vjsClass = "vjs-16-9";
    $embedResponsiveClass = "embed-responsive-16by9";
}
?>
<div class="row main-video" id="mvideo">
    <div class="col-sm-2 col-md-2 firstC"></div>
    <div class="col-sm-8 col-md-8 secC">
        <div id="videoContainer">
            <div id="floatButtons" style="display: none;">
                <p class="btn btn-outline btn-xs move">
                    <i class="fas fa-expand-arrows-alt"></i>
                </p>
                <button type="button" class="btn btn-outline btn-xs"
                        onclick="closeFloatVideo(); floatClosed = 1;">
                    <i class="far fa-window-close"></i>
                </button>
            </div>
            <div id="main-video" class="embed-responsive <?php echo $embedResponsiveClass; ?>">
                <video
                <?php if ($config->getAutoplay() && false) { // disable it for now  ?>
                        autoplay="true"
                        muted="muted"
                    <?php } ?>
                    preload="auto"
                    poster="<?php echo $poster; ?>" controls class="embed-responsive-item video-js vjs-default-skin <?php echo $vjsClass; ?> vjs-big-play-centered" id="mainVideo" data-setup='{ "aspectRatio": "<?php echo $aspectRatio; ?>" }'>
                        <?php if ($playNowVideo['type'] == "video") { ?>
                        <!-- <?php echo $playNowVideo['title'], " ", $playNowVideo['filename']; ?> -->
                        <?php
                        echo getSources($playNowVideo['filename']);
                    } else {
                        ?>
                        <source src="<?php echo $playNowVideo['videoLink']; ?>" type="video/mp4" >
                    <?php } ?>
                    <p><?php echo __("If you can't view this video, your browser does not support HTML5 videos"); ?></p>
                    <p class="vjs-no-js"><?php echo __("To view this video please enable JavaScript, and consider upgrading to a web browser that"); ?>
                        <a href="http://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
                    </p>
                </video>
                <?php
                require_once $global['systemRootPath'] . 'plugin/YouPHPTubePlugin.php';
                // the live users plugin
                if (YouPHPTubePlugin::isEnabled("0e225f8e-15e2-43d4-8ff7-0cb07c2a2b3b")) {
                    require_once $global['systemRootPath'] . 'plugin/VideoLogoOverlay/VideoLogoOverlay.php';
                    $style = VideoLogoOverlay::getStyle();
                    $url = VideoLogoOverlay::getLink();
                    ?>
                    <div style="<?php echo $style; ?>">
                        <a href="<?php echo $url; ?>"> <img src="<?php echo $global['webSiteRootURL']; ?>videos/logoOverlay.png" class="img-responsive col-lg-12 col-md-8 col-sm-7 col-xs-6"></a>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>
    <div class="col-sm-2 col-md-2"></div>
</div>
<!--/row-->
<script>
<?php $_GET['isMediaPlaySite'] = $playNowVideo['id']; ?>

    var mediaId = <?php echo $playNowVideo['id']; ?>;
    var player;
    $(document).ready(function () {

<?php
if ($playNowVideo['type'] == "linkVideo") {
    echo '$("time.duration").hide();';
}
?>

        var menu = new BootstrapMenu('#mainVideo', {
        actions: [{
        name: '<?php echo __("Copy video URL"); ?>',
                onClick: function () {
                    copyToClipboard($('#linkFriendly').val());
                }, iconClass: 'fas fa-link'
        }, {
        name: '<?php echo __("Copy video URL at current time"); ?>',
                onClick: function () {
                    copyToClipboard($('#linkCurrentTime').val());
                }, iconClass: 'fas fa-link'
        }, {
        name: '<?php echo __("Copy embed code"); ?>',
                onClick: function () {
                    $('#textAreaEmbed').focus();
                    copyToClipboard($('#textAreaEmbed').val());
                }, iconClass: 'fas fa-code'
        }
<?php if ($config->getAllow_download()) { ?>
    <?php
    if ($playNowVideo['type'] == "video") {
        $files = getVideosURL($playNowVideo['filename']);
        foreach ($files as $key => $theLink) {
            ?>
                    , {
                        name: '<?php echo __("Download video") . " (" . $key . ")"; ?>',
                        onClick: function () {
                            document.location = '<?php echo $theLink['url']; ?>?download=1&title=<?php echo urlencode($video['title'] . "_{$key}_.mp4"); ?>';
                                        }, iconClass: 'fas fa-download'
                                    }
            <?php
        }
    } else {
        ?>
                                , {
                                    name: '<?php echo __("Download video"); ?>',
                                    onClick: function () {
                                        document.location = '<?php echo $video['videoLink']; ?>?download=1&title=<?php echo urlencode($video['title'] . ".mp4"); ?>';
                                                    }, iconClass: 'fas fa-download'
                                                }

        <?php
    }
}
?>

                                        ]
                                    });



                                    player = videojs('mainVideo');
                                    player.zoomrotate(<?php echo $transformation; ?>);
                                    player.on('play', function () {
                                        addView(<?php echo $playNowVideo['id']; ?>);
                                    });
                                    player.ready(function () {
<?php
if (!empty($_GET['t'])) {
    ?>
                                            player.currentTime(<?php echo intval($_GET['t']); ?>)
    <?php
}
?>

<?php if ($config->getAutoplay()) {
    ?>
                                            setTimeout(function () {
                                                if (typeof player === 'undefined') {
                                                    player = videojs('mainVideo');
                                                }
                                                try {
                                                    player.play();
                                                } catch (e) {
                                                    setTimeout(function () {
                                                        player.play();
                                                    }, 1000);
                                                }
                                            }, 150);
<?php } else {
    ?>
                                            if (Cookies.get('autoplay') && Cookies.get('autoplay') !== 'false') {
                                                setTimeout(function () {
                                                    if (typeof player === 'undefined') {
                                                        player = videojs('mainVideo');
                                                    }
                                                    try {
                                                        player.play();
                                                    } catch (e) {
                                                        setTimeout(function () {
                                                            player.play();
                                                        }, 1000);
                                                    }
                                                }, 150);
                                            }
<?php }
?>
                                        this.on('ended', function () {
                                            console.log("Finish Video");
<?php
// if autoplay play next video
if (!empty($autoPlayVideo)) {
    ?>
                                                if (Cookies.get('autoplay') && Cookies.get('autoplay') !== 'false') {
    <?php
    if ($autoPlayVideo['type'] !== 'video' || empty($advancedCustom->autoPlayAjax)) {
        ?>

                                                        document.location = autoPlayURL;
        <?php
    } else {
        ?>
                                                        $('video, #mainVideo').attr('poster', autoPlayPoster);
                                                        changeVideoSrc(player, autoPlaySources);
                                                        history.pushState(null, null, autoPlayURL);
                                                        $('.vjs-thumbnail-holder, .vjs-thumbnail-holder img').attr('src', autoPlayThumbsSprit);
                                                        $.ajax({
                                                            url: autoPlayURL,
                                                            success: function (response) {
                                                                modeYoutubeBottom = $(response).find('#modeYoutubeBottom').html();
                                                                $('#modeYoutubeBottom').html(modeYoutubeBottom);
                                                                //pluginFooterCode = $(response).filter('#pluginFooterCode').html();
                                                                //$('#pluginFooterCode').html(pluginFooterCode);
                                                            }
                                                        });
        <?php
    }
    ?>
                                                }
<?php } ?>

                                        });

                                        this.on('timeupdate', function () {
                                            var time = Math.round(this.currentTime());
                                            $('#linkCurrentTime').val('<?php echo Video::getURLFriendly($video['id']); ?>?t=' + time);
                                        });
                                    });
                                    player.persistvolume({
                                        namespace: "YouPHPTube"
                                    });
                                    // in case the video is muted
                                    setTimeout(function () {
                                        if (player.muted()) {
                                            swal({
                                                title: "<?php echo __("Your Media is Muted"); ?>",
                                                text: "<?php echo __("Would you like to unmute it?"); ?>",
                                                type: "warning",
                                                showCancelButton: true,
                                                confirmButtonColor: "#DD6B55",
                                                confirmButtonText: "<?php echo __("Yes, unmute it!"); ?>",
                                                closeOnConfirm: true
                                            },
                                                    function () {
                                                        player.muted(false);
                                                    });
                                        }
                                    }, 500);
                                    }
                                    );
</script>
