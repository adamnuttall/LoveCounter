function lovecounteraddvote(postId)
{
	jQuery.ajax({
	type: 'POST',
	url: lovecounterajax.ajaxurl,
	data: {
	  action: 'lovecounter_addvote',
	  postid: postId
  },
  success:function(data, textStatus, XMLHttpRequest){
	  var linkid = '#lovecounter-' + postId;
	  jQuery(linkid).html('');
	  jQuery(linkid).append(data);
	},
	error: function(MLHttpRequest, textStatus, errorThrown){
		alert(errorThrown);
		}
	});
}

jQuery(document).ready(function () {
    jQuery('.lovecounter').click(function (e) {
        var fullID, postID;

        fullID = jQuery(this).attr("id");
        postID = fullID.substring(19, fullID.length);
        
        lovecounteraddvote(postID);
    });
});

