/*
 * @copyright Copyright (C) 2014 iJoomla, Inc. - All Rights Reserved.
 * @license GNU General Public License, version 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @author PeepSo.com <webmaster@peepso.com>
 * @url https://www.peepso.com/license-agreement
 * The PHP code portions are distributed under the GPL license. If not otherwise stated, all images, manuals, cascading style sheets, and included JavaScript *are NOT GPL, and are released under the iJoomla Proprietary Use License v1.0
 * More info at https://www.peepso.com/license-agreement/
 *///$PeepSo.log("starting activitystream.js");
/*
 * Handles user interactions for the Activity Stream
 * @package PeepSo
 * @author PeepSo
 */function PsActivity(){this.current_post=null}var activity=new PsActivity;PsActivity.prototype.init=function(e){};PsActivity.prototype.action_like=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.like",t,function(t){if(t.success){var n=t.data.html;jQuery("#act-like-"+e).html(n).show()}else alert(t.errors[0])});return!1};PsActivity.prototype.action_share=function(e){return!1};PsActivity.prototype.action_report=function(e){var t=jQuery("#activity-report-title").html(),n=jQuery("#activity-report-content").html(),r=jQuery(".ps-js-activity--"+e+" .cstream-attachment").html().trim()+"!~";r=r.replace("<p>","<span>").replace("</p>","<br/></span>").replace("<br/></span>!~","</span>");n=n.replace("{post-content}",r);n=n.replace("{post-id}",e+"");pswindow.show(t,n)};PsActivity.prototype.submit_report=function(){var e=jQuery("#postbox-report-popup #postbox-post-id","#ps-window").val(),t=jQuery("#rep_reason option:selected","#ps-window").val(),n={postid:e,uid:peepsodata.currentuserid,reason:t},r=ps_observer.apply_filters("activitystream_notice_container",".ps-js-activity--"+e+" .cstream-more",e);$PeepSo.getJson("activity.report",n,function(e){jQuery(r).html(e.notices[0])});pswindow.hide();return!1};PsActivity.prototype.action_delete=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.delete",t,function(t){t.success&&jQuery(".ps-js-activity--"+e).remove()});return!1};PsActivity.prototype.comment_save=function(e){var t="#act-new-comment-"+e+" .cstream-form-text";jQuery("#act-new-comment-"+e+" .ps-comment-loading").show();jQuery("#act-new-comment-"+e+" .ps-comment-actions").hide();var n=jQuery(t).val();req={postid:e,uid:peepsodata.currentuserid,content:n};$PeepSo.postJson("activity.makecomment",req,function(t){t.success?jQuery("#act-comment-container-"+e).append(t.data.html):alert("problem posting comment");activity.comment_cancel(e);activity.current_post=null;jQuery("#act-new-comment-"+e+" .ps-comment-loading").hide();jQuery("#act-new-comment-"+e+" .ps-comment-actions").show()});return!1};PsActivity.prototype.comment_action_delete=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.delete",t,function(t){t.success&&jQuery("#comment-item-"+e).remove()});return!1};PsActivity.prototype.comment_action_report=function(e){var t=jQuery("#activity-report-title").html(),n=jQuery("#activity-report-content").html(),r=jQuery("#comment-item-"+e+" .comment").html().trim()+"!~";r=r.replace("<p>","<span>").replace("</p>","<br/></span>").replace("<br/></span>!~","</span>");n=n.replace("{post-content}",r);n=n.replace("{post-id}",e+"");ps_observer.add_filter("activitystream_notice_container",function(e,t){return"#comment-item-"+t+" .cstream-more"},10,2);pswindow.show(t,n);jQuery("#ps-window").one("pswindow.hidden",function(){ps_observer.remove_filter("activitystream_notice_container",function(e,t){return"#comment-item-"+t+" .cstream-more"},10)})};PsActivity.prototype.comment_action_share=function(e){};PsActivity.prototype.comment_action_like=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.like",t,function(t){if(t.success){var n=t.data.html;jQuery("#comment-item-"+e).append(n)}else alert(t.errors[0])});return!1};PsActivity.prototype.comment_cancel=function(e){jQuery("#act-new-comment-"+e+" .cstream-form-text").val("");return!1};PsActivity.prototype.show_likes=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.getlikes",t,function(t){jQuery("#act-like-"+e).html("several people like this")});return!1};PsActivity.prototype.show_comments=function(e){var t={postid:e,uid:peepsodata.currentuserid};jQuery("#wall-cmt-"+e+" .comment-ajax-loader").toggleClass("hidden");$PeepSo.getJson("activity.show_remaining_comments",t,function(t){jQuery("#wall-cmt-"+e+" .cstream-more").fadeOut();jQuery("#wall-cmt-"+e+" .comment-ajax-loader").toggleClass("hidden");jQuery("#act-comment-container-"+e).append(t.data.html)});jQuery(this).hide();return!1};PsActivity.prototype.option_edit=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.editpost",t,function(t){if(t.success){var n=".ps-js-activity--"+e+" .cstream-content .cstream-attachment";jQuery(".ps-js-activity--"+e+" .cstream-attachment").hide().after(t.data.html);jQuery(".ps-js-activity--"+e+" .cstream-content .cstream-edit textarea").autosize()}})};PsActivity.prototype.option_canceledit=function(e){var t=jQuery(".ps-js-activity--"+e);if(t.length>0){jQuery(".cstream-edit",t).remove();jQuery(".cstream-attachment",t).show()}};PsActivity.prototype.option_savepost=function(e){var t=jQuery(".ps-js-activity--"+e);if(t.length>0){var n=jQuery(".cstream-edit textarea",t).val();jQuery(".cstream-edit",t).remove();var r={postid:e,uid:peepsodata.currentuserid,post:n};$PeepSo.postJson("activity.savepost",r,function(e){jQuery(".cstream-attachment",t).html(e.data.html).show()})}};PsActivity.prototype.option_hide=function(e){var t={postid:e,uid:peepsodata.currentuserid};$PeepSo.postJson("activity.hidepost",t,function(t){t.success&&jQuery(".ps-js-activity--"+e).remove()})};PsActivity.prototype.option_block=function(e,t){var n={uid:peepsodata.currentuserid,user_id:t};$PeepSo.postJson("activity.blockuser",n,function(t){t.success&&jQuery(".ps-js-activity--"+e).remove()})};jQuery(document).ready(function(){activity.init()});