<?php
// check permissions
if (!$g_user->hasPermission('CommentModerate')) {
    return;
}
?>

<?php
// add token
echo SecurityToken::FormParameter();

// add hidden inputs
$hiddens = array(
    'f_language_id' => 'language_id',
    'f_article_number' => 'article_id',
    'f_language_selected' => 'language_selected_id',
);
foreach ($hiddens as $name) {
    if (!isset($$name)) {
        $$name = '';
    }

    echo '<input type="hidden" name="', $name;
    echo '" value="', $$name, '" />', "\n";
}
/** @todo Replace this basic template with a doT template from jquery*/
?>
<fieldset id="comment-prototype" class="plain comments-block" style="display:none">
    <input type="hidden" name="comment_id" value="${id}">
    <?php if ($inEditMode): ?>
    <ul class="action-list clearfix">
      <li>
        <a class="ui-state-default icon-button right-floated" href="javascript:;"><span class="ui-icon ui-icon-disk"></span><?php putGS('Save'); ?></a>
      </li>
      
      <li>
        <input type="radio" name="comment_action_${id}" value="deleted" class="input_radio" id="deleted_${id}" ${deleted_checked}/>
        <label class="inline-style left-floated" for="deleted_${id}"><?php putGS('Delete'); ?></label>
      </li>
      
      <li>
        <input type="radio" name="comment_action_${id}" value="hidden" class="input_radio" id="hidden_${id}" ${hidden_checked}/>
        <label class="inline-style left-floated" for="hidden_${id}"><?php putGS('Hidden'); ?></label>
      </li>

      <li>
      <input type="radio" name="comment_action_${id}" value="approved" class="input_radio" id="approved_${id}" ${approved_checked}/>
        <label class="inline-style left-floated" for="approved_${id}"><?php putGS('Approved'); ?></label>
      </li>

      <li>
      <input type="radio" name="comment_action_${id}" value="pending" class="input_radio" id="inbox_${id}" ${pending_checked}/>
        <label class="inline-style left-floated" for="inbox_${id}"><?php putGS('New'); ?></label>
      </li>
    </ul>
    <?php endif; //inEditMode?>
    <div class="frame clearfix">
      <dl class="inline-list" id="comment-${id}">
        <dt><?php putGS('From'); ?></dt>
        <dd><a href="mailto:${email}">"${name}" &lt;${email}&gt;</a> (${ip})</dd>
        <dt><?php putGS('Date'); ?></dt>
        <dd>${time_created}</dd>
        <dt><?php putGS('Subject'); ?></dt>
        <dd>${subject}</dd>
        <dt><?php putGS('Comment'); ?></dt>
        <dd>${message}</dd>
        <?php if ($inEditMode): ?>
        <dt>&nbsp;</dt>
        <dd class="buttons">
            <a href="<?php echo camp_html_article_url($articleObj, $f_language_selected, 'comments/reply.php', '', '&f_comment_id=${id}'); ?>" class="ui-state-default text-button clear-margin"><?php putGS('Reply to comment'); ?></a>
            <span style="float:left">&nbsp;</span>
            <a href="<?php echo $controller->view->url(array(
                'module' => 'admin',
                'controller' => 'comment',
                'action' => 'set-recommended',
            )); ?>/comment/${id}/recommended/${recommended_toggle}" class="ui-state-default text-button clear-margin comment-recommend status-${recommended_toggle}"><?php putGS('Recommend'); ?></a>
        </dd>
        <?php endif; //inEditMode?>
      </dl>
    </div>
</fieldset>
<p style="display:none"><?php putGS('No comments posted.'); ?></p>
<form id="comment-moderate" action="../comment/set-status/format/json" method="POST"></form>
<script type="text/javascript">
function toggleCommentStatus(commentId) {
    var commentSetting = $('input:radio[name^="f_comment"]:checked').val();
    $('#comment-moderate .comments-block').each(function() {
    	if (commentId && commentId == $(this).find('input:hidden').val()) {
            var statusClassMap = { 'hidden': 'hide', 'approved': 'approve', 'pending': 'inbox'};
            var block = $(this);
            var status = $('input:radio:checked', block).val();
            
            var cclass = 'comment_'+statusClassMap[status];
            var button = $('dd.buttons', block);

            // set class
            $('.frame', block).removeClass('comment_inbox comment_hide comment_approve')
                .addClass(cclass);
            // show/hide button
            button.hide();
            if ((status == 'approved') && (commentSetting != 'locked')) {
                button.show();
            }
        }
    });
    //detach deleted
    $('input[value=deleted]:checked', $('#comment-moderate')).each(function() {
        $(this).closest('fieldset').slideUp(function() {
            $(this).detach();
        });
    });
}
function loadComments() {

	var call_data = {
		"article": "<?php echo $articleObj->getArticleNumber(); ?>",
		"language": "<?php echo $f_language_selected; ?>"
	};

    var call_url = '../comment/list/format/json';

	var res_handle = function(data) {
		$('#comment-moderate').empty();
		var hasComment = false;
		for(var i in data.result) {
			hasComment = true;
			var comment = data.result[i];
			if(typeof(comment) == "function") {
				continue;
			}

			var template = $('#comment-prototype').html();
			for(var key in comment) {
				if(key == 'status') {
					template = template.replace(new RegExp("\\$({|%7B)"+comment[key]+"_checked(}|%7D)","g"),'checked="true"');
					template = template.replace(new RegExp("\\${[^_]*_checked}","g"),'');
				}
				template = template.replace(new RegExp("\\$({|%7B)"+key+"(}|%7D)","g"),comment[key]);
			}
			$('#comment-moderate').append('<fieldset class="plain comments-block">'+template+'</fieldset>');

		}

        var referencedComment = $(document.location.hash);
        if (referencedComment.size() == 1) {
            $(window).scrollTop(referencedComment.position().top);
        }

		if(!hasComment)
			$('#no-comments').show();
		toggleCommentStatus();

        $('.comment-recommend').each(function() {
             if ($(this).hasClass('status-0')) {
                 $(this).html("<?php putGS("Unrecommend"); ?>");
            }
        }).click(function() {
            var link = $(this);
            $.getJSON($(this).attr('href') + '?format=json', {
                'security_token': g_security_token
            }, function(data, textStatus, jqXHR) {
                if (link.hasClass('status-0')) {
                    link.removeClass('status-0').addClass('status-1');
                    link.html("<?php putGS("Recommend"); ?>");
                    var status = 1;
                } else {
                    link.removeClass('status-1').addClass('status-0');
                    link.html("<?php putGS("Unrecommend"); ?>");
                    var status = 1;
                }

                var href = link.attr('href');
                link.attr('href', href.substr(0, href.length - 2) + status);
            });

            return false;
        });
	};

	callServer(call_url, call_data, res_handle, true);
};
$('.action-list a').live('click',function(){
	var el = $(this).parents('ul').find('input:checked').first();

	var call_data = {
	   "comment": el.attr('id').match(/\d+/)[0],
	   "status": el.val()
	};
    
    var call_url = '../comment/set-status/format/json';

	var res_handle = function(data) {
		flashMessage('<?php putGS('Comments updated.'); ?>');
		toggleCommentStatus(el.attr('id').match(/\d+/)[0]);
	};

	callServer(call_url, call_data, res_handle, true);
});
</script>
<script type="text/javascript">
$(function() {
	loadComments();
});
</script>
