<div class="peepso">
    <section id="mainbody" class="ps-wrapper clearfix">
        <section id="component" role="article" class="clearfix">
            <?php peepso('load-template', 'general/navbar'); ?>
            <div id="cProfileWrapper" class="clearfix">
                <?php peepso('load-template', 'profile/focus'); ?>

                <div id="editLayout-stop" class="page-action" style="display: none;">
                    <a onclick="profile.editLayout.stop()" href="javascript:void(0)"><?php _e('Finished Editing Apps Layout', 'peepso'); ?></a>
                </div>

                <div class="ps-body">
                    <?php
                    // widgets top
                    $widgets_profile_sidebar_top = apply_filters('peepso_widget_prerender', 'profile_sidebar_top');

                    // widgets bottom
                    $widgets_profile_sidebar_bottom = apply_filters('peepso_widget_prerender', 'profile_sidebar_bottom');

                    // widgets main
                    $widgets_profile_main = apply_filters('peepso_widget_prerender', 'profile_main');
                    ?>

                    <?php
                    if (peepso('profile', 'get.has-sidebar') || count($widgets_profile_sidebar_top) > 0 || count($widgets_profile_sidebar_bottom) > 0) { ?>
                        <?php peepso('load-template', 'sidebar/sidebar', array('profile_sidebar_top'=>$widgets_profile_sidebar_top, 'profile_sidebar_bottom'=>$widgets_profile_sidebar_bottom, )); ?>
                    <?php } ?>

                    <div class="ps-main <?php if (peepso('profile', 'get.has-sidebar') || count($widgets_profile_sidebar_top) > 0 || count($widgets_profile_sidebar_bottom) > 0) echo ''; else echo 'ps-main-full'; ?>">
                            <!-- js_profile_feed_top -->
                            <div class="activity-stream-front">
                                <?php peepso('load-template', 'general/postbox'); ?>

                                <div class="ps-latest-activities-container" data-actid="-1" style="display: none;">
                                    <a id="activity-update-click" class="btn btn-block" href="javascript:void(0);"></a>
                                </div>

                                <div class="ps-tab-bar">
                                    <a class="active" href="javascript:void();" was="stream" data-toggle="tab" onclick="profile.activate_tab(event, '#stream', '#about'); return false;"><?php _e('Stream', 'peepso'); ?></a>
                                    <a href="javascript:void();" was="about" data-toggle="tab" onclick="profile.activate_tab(event, '#about', '#stream'); return false;"><?php _e('About Me', 'peepso'); ?></a>

                                    <!-- widgetize -->

                                    <?php if (count($widgets_profile_main) > 0) {
                                        foreach($widgets_profile_main as $widget) {
                                                ?>
                                                <a href="#<?php echo $widget['widget_class'];?>" data-toggle="tab"><?php echo $widget['title'];?></a>
                                            <?php
                                            }

                                        ?>

                                    <?php } ?>
                                    <!-- /widgetize -->
                                </div>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="stream">
                                        <div id="ps-activitystream" class="ps-stream-container cstream-list creset-list" data-filter="all" data-filterid="0" data-groupid data-eventid data-profileid>
                                            <?php
                                            if (peepso('activity', 'has-posts')) {
                                                // display all posts
                                                while (peepso('activity', 'next-post')) {
                                                    peepso('activity', 'show-post'); // display post and any comments
                                                }

                                                peepso('activity', 'show-more-posts-link');
                                            }
                                            ?>
                                        </div>
                                    </div>

                                    <div class="ps-content-wrapper tab-pane" id="about">
                                        <?php peepso('profile', 'user-profile-fields'); ?>
                                    </div>

                                    <!-- widgetize -->
                                    <?php
                                    if (count($widgets_profile_main) > 0) {

                                        foreach($widgets_profile_main as $widget) {
                                                ?>
                                                <div class="ps-tab-content tab-pane" id="<?php echo $widget['widget_class'];?>">
                                                    <?php
                                                    $widget['is_profile_widget'] = TRUE;
                                                    the_widget($widget['widget_class'], $widget);
                                                    ?>
                                                </div>
                                            <?php
                                            }

                                    } ?>
                                    <!-- /widgetize -->
                                </div>
                            </div><!-- end activity-stream-front -->

                            <?php peepso('load-template', 'activity/dialogs'); ?>
                            <div id="apps-sortable" class="connectedSortable"></div>
                    </div><!-- cMain -->
                </div><!-- end row -->
            </div><!-- end cProfileWrapper --><!-- js_bottom -->
            <div id="ps-dialogs" style="display:none">
                <?php peepso('profile', 'dialogs'); // give add-ons a chance to output some HTML ?>
            </div>
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
