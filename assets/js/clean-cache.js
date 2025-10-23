"use strict";

(function($) {
    function clean_weekday_cache(){
        $(document).on('click', '.mwhp-clear-cache', function(e){
            e.preventDefault();
            var $el = $(this);
            var postId = $el.data('postid');
            var nonce  = $el.data('nonce');

            if ( ! postId ) return;

            if ( ! confirm('Clear cached business hours for this post?') ) return;

            $.post( mwhpJSObj.ajax_url, {
                action: 'mwhp_clear_cache',
                post_id: postId,
                nonce: mwhpJSObj.nonce
            }, function( resp ){
                if ( resp && resp.success ) {
                    location.reload();
                } else {
                    var msg = (resp && resp.data) ? resp.data : 'Could not clear cache';
                    alert('Error: ' + msg );
                }
            }, 'json' ).fail(function(){
                alert('Request failed. Check your console/network.');
            });
        });
    }
    $(document).ready(function() {
        clean_weekday_cache();
      
    });
})(jQuery);