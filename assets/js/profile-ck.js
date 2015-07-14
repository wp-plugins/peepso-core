/*
 * Handlers for user profile page
 * @package PeepSo
 * @author PeepSo
 *///$PeepSo.log("profile.js");
// declare class
function PsProfile(){}function PsBookmarks(){}var profile=new PsProfile;PsProfile.prototype.init=function(){var e=jQuery(".js-collapse-about-btn");e.length!=0&&e.on("click",function(e){e.preventDefault();var t=jQuery(".js-collapse-about"),n=t.css("display");n=="none"?t.show():t.hide()})};PsProfile.prototype.new_like=function(){var e={likeid:peepsodata.userid,uid:peepsodata.currentuserid};$PeepSo.postJson("profile.like",e,function(e){e.success?jQuery("#like-count").html(e.data.like_count):alert(e.errors[0])});return!1};PsProfile.prototype.report=function(){emptyMessage="* Message cannot be left empty";profile.report.showWindow(peepsodata.currentuserid);return!1};PsBookmarks.prototype={show:function(e,t){var n="alert('this is a test');";cWindowShow(n,"",450,100)},email:function(e){var t=jax.getFormValues("bookmarks-email"),n=t[1][1],r=t[0][1],i="jax.call('community', 'bookmarks,ajaxEmailPage','"+e+"','"+r+"',\""+n+'");';cWindowShow(i,"",450,100)}};var bookmark=new PsBookmarks;jQuery(document).ready(function(){profile.init()});