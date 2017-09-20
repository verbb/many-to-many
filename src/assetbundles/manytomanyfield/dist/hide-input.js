$( document ).ready(function() {
    var myTarget = $('.hud .elementeditor .mTm-modal-hide');
    if (myTarget.length) {
        myTarget.closest('.field').remove();
    }
});
