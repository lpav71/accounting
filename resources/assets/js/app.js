/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./_config');
require('./_common');
require('./app/_common/sidebar/sidebar');
require('./app/_common/async-select');
require('./vendor/x-editable/bootstrap4-editable/bootstrap-editable');
require('./vendor/translation-manager/translation-manager');
require('./products/products');
require('./orders/orders');
require('./route-lists/route-lists');
require('./tasks/tasks');
require('./order-states/order-states');
require('./analytics/analytics');
require('./analytics/users');
require('./vendor/upload_image_preview');
require('./vendor/summernote_editor_settings');
require('./channels/notification-templates');
require('./products/presta_product');
require('./messengers/messengers');
require('./stores/stores');
require('./cashbox/cashbox');
require('./tickets/tickets');
require('./ticket-event-actions/ticket-event-actions');
require('./rule-order/rule-order');

$(function () {

    $("body").addClass("loaded");
    $('.selectpicker-searchable').selectpicker({
        liveSearch: true,
    });
    $('.selectpicker-ajax').selectpicker().ajaxSelectPicker();

    let $workTimeModal = $('#work-time');

    if (typeof $workTimeModal !== "undefined") {
        $workTimeModal.modal({
            backdrop: 'static',
            keyboard: false
        });
    }

});

(function(){

    let pcastPlayers = document.querySelectorAll('.pcast-player');
    let speeds = [ 1, 1.5, 2, 2.5, 3 ];

    for(i=0;i<pcastPlayers.length;i++) {
        let player = pcastPlayers[i];
        let audio = player.querySelector('audio');
        let play = player.querySelector('.pcast-play');
        let pause = player.querySelector('.pcast-pause');
        let rewind = player.querySelector('.pcast-rewind');
        let progress = player.querySelector('.pcast-progress');
        let speed = player.querySelector('.pcast-speed');
        let mute = player.querySelector('.pcast-mute');
        let currentTime = player.querySelector('.pcast-currenttime');
        let duration = player.querySelector('.pcast-duration');

        let currentSpeedIdx = 0;

        pause.style.display = 'none';

        let toHHMMSS = function ( totalsecs ) {
            let sec_num = parseInt(totalsecs, 10); // don't forget the second param
            let hours   = Math.floor(sec_num / 3600);
            let minutes = Math.floor((sec_num - (hours * 3600)) / 60);
            let seconds = sec_num - (hours * 3600) - (minutes * 60);

            if (hours   < 10) {hours   = "0"+hours; }
            if (minutes < 10) {minutes = "0"+minutes;}
            if (seconds < 10) {seconds = "0"+seconds;}

            let time = hours+':'+minutes+':'+seconds;
            return time;
        };

        audio.addEventListener('loadedmetadata', function(){
            progress.setAttribute('max', Math.floor(audio.duration));
            duration.textContent  = toHHMMSS(audio.duration);
        });

        audio.addEventListener('timeupdate', function(){
            progress.setAttribute('value', audio.currentTime);
            currentTime.textContent  = toHHMMSS(audio.currentTime);
        });

        play.addEventListener('click', function(e){
            e.preventDefault();
            this.style.display = 'none';
            pause.style.display = 'inline-block';
            pause.focus();
            audio.play();
        }, false);

        pause.addEventListener('click', function(e){
            e.preventDefault();
            this.style.display = 'none';
            play.style.display = 'inline-block';
            play.focus();
            audio.pause();
        }, false);

        rewind.addEventListener('click', function(e){
            e.preventDefault();
            audio.currentTime -= 30;
        }, false);

        progress.addEventListener('click', function(e){
            e.preventDefault();
            audio.currentTime = Math.floor(audio.duration) * (e.offsetX / e.target.offsetWidth);
        }, false);

        speed.addEventListener('click', function(e){
            e.preventDefault();
            currentSpeedIdx = currentSpeedIdx + 1 < speeds.length ? currentSpeedIdx + 1 : 0;
            audio.playbackRate = speeds[currentSpeedIdx];
            this.textContent  = speeds[currentSpeedIdx] + 'x';
            return true;
        }, false);

        mute.addEventListener('click', function(e) {
            e.preventDefault();
            if(audio.muted) {
                audio.muted = false;
                this.querySelector('.fa').classList.remove('fa-volume-off');
                this.querySelector('.fa').classList.add('fa-volume-up');
            } else {
                audio.muted = true;
                this.querySelector('.fa').classList.remove('fa-volume-up');
                this.querySelector('.fa').classList.add('fa-volume-off');
            }
        }, false);
    }
})(this);
