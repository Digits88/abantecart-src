<meta charset="utf-8">
<title><?php echo $title; ?></title>
<base href="<?php echo $base; ?>"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

<?php foreach ($links as $link) { ?>
<link href="<?php echo $link['href']; ?>" rel="<?php echo $link['rel']; ?>"/>
<?php } ?>

<?php if (is_file(DIR_RESOURCE . $icon)) { ?>
<link href="<?php echo HTTP_DIR_RESOURCE . $icon; ?>" type="image/png" rel="icon"/>
<?php } else if (!empty($icon)) { ?>
<?php echo $icon; ?>
<?php } ?>

<link rel="stylesheet" type="text/css" href="<?php echo $template_dir; ?>stylesheet/stylesheet.css" />

<?php foreach ($styles as $style) { ?>
<link rel="<?php echo $style['rel']; ?>" type="text/css" href="<?php echo $style['href']; ?>"
      media="<?php echo $style['media']; ?>"/>
<?php } ?>

<script type="text/javascript"
        src="<?php echo $ssl ? 'https' : 'http'?>://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script type="text/javascript">
    if (typeof jQuery == 'undefined') {
        var include = '<script type="text/javascript" src="<?php echo $template_dir; ?>javascript/jquery/jquery-1.11.0.min.js"><\/script>';
        document.write(include);
    }
</script>
<script type="text/javascript" src="<?php echo $template_dir; ?>javascript/jquery/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="<?php echo $template_dir; ?>javascript/jquery/jquery-ui/jquery-ui-1.10.4.custom.min.js"></script>
<script type="text/javascript" src="<?php echo $template_dir; ?>javascript/bootstrap.min.js"></script>

<?php foreach ($scripts as $script) { ?>
<script type="text/javascript" src="<?php echo $script; ?>"></script>
<?php } ?>

<script type="text/javascript" src="<?php echo $template_dir; ?>javascript/aform.js"></script>

<?php 
	//Generic PHP processed Javascript section
?>
<script type="text/javascript">
$(document).ready(function () {
  
	numberSeparators = {decimal:'<?php echo $decimal_point; ?>', thousand:'<?php echo $thousand_point; ?>'};

});

var wrapConfirmDelete = function(){
    var wrapper = '<div class="btn-group dropup" />';
    var popover, href;

    $('a[data-confirmation="delete"]').each( function(){
        if($(this).attr('data-toggle')=='dropdown' ){ return;}
        
       	var action = $(this).attr('onclick');
        if ( action ) {
        	action = 'onclick="'+action+'"';
        } else {
	        href = $(this).attr('href');
			if(!href){ return;}
    	    if(href.length==0 || href=='#'){ return;}
    	    action = 'href="' + href +'"';
        }
        
    	var conf_text = $(this).attr('data-confirmation-text');
    	if (!conf_text) {
    		conf_text = '<?php echo $text_confirm; ?>';
    	} 
        
        $(this).wrap(wrapper);
        popover = '<div class="dropdown-menu dropdown-menu-right alert alert-danger" role="menu">'+
                    '<h5 class="center">'+ conf_text +'</h5>'+
                    '<div class="center">'+
                    '<a class="btn btn-danger" '+action+' ><i class="fa fa-trash-o"></i> Yes</a>&nbsp;&nbsp;'+
                    '<a class="btn btn-default"><i class="fa fa-undo"></i> No</a>'+
                    '</div>'+
                    '</div>';
        $(this).after(popover);
        $(this).attr('onclick','return false;');
        $(this).attr('data-toggle','dropdown').addClass('dropdown-toggle');
    });
}




//periodical updater of new message notifier
var notifier_updater = function () {
  $.ajax({
	url: '<?php echo $notifier_updater_url?>',
	success: buildNotifier,
	complete: function() {
	  // Schedule the next request when the current one's complete
	  setTimeout(notifier_updater, 600000);
	}
  });
}

var buildNotifier = function(data){
	$('.new_messages .badge').html(data.total);
	$('.new_messages .dropdown-menu-head h5.title').html(data.total_title);
	var  list = $('.new_messages ul.dropdown-list.gen-list');
	list.html('');
	var html = '';
	var mes;
	for(var k in data.shortlist){
		mes = data.shortlist[k];

		var iconclass='';
		var badgeclass='';
		if(mes.status=='N'){
			iconclass = 'fa-info';
			badgeclass = 'success';
		}else if(mes.status=='W' || mes.status=='E'){
			iconclass = 'fa-warning';
			badgeclass = 'danger';
		}
		html = '<li '+ (mes.viewed<1 ? 'class="new"': '')+ '>' ;
		html += '<a href="'+mes.href+'" data-toggle="modal" data-target="#message_modal"><span class="thumb"><p class="fa ' + iconclass + ' fa-3 '+badgeclass+'"></p></span>';
		html += '<span class="desc"><span class="name">'+mes.title + (mes.viewed<1 ? '<span class="badge badge-'+badgeclass+'">new</span>': '')+'</span>';
		html += '<span class="msg">'+mes.message +'</span></span></a></li>';
		list.append(html);
	}

	list.append('<li class="new"><a href="<?php echo $message_manager_url; ?>"><?php echo $text_read_all_messages; ?></a></li>');
}

$(document).ready(function(){
	notifier_updater();
	$(document).on('click', '#message_modal a[data-dismiss="modal"], #message_modal button[data-dismiss="modal"]', notifier_updater );
});

</script>
<?php 
	//NOTE: More JS loaded in page.tpl. This is to improve performance. Do not move above to page.tpl
?>