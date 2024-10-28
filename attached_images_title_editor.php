<?php
/*
Plugin Name: Attached images title editor
Plugin URI: http://hellomynameisjuan.com/
Description: This plugin has the hability to change the title of all the images attached to a post by inserting a new title for them all.
Version: 1.1.1
Author: Juan Manuel Incaurgarat
Author URI: http://hellomynameisjuan.com/
License: GPL2
*/
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class TT_List_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'image',     //singular name of the listed records
            'plural'    => 'images',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function column_default($item, $column_name){
        switch($column_name){
            case 'name':
            case 'post_title':
	    case 'post_date':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_post_title($item){
        
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&postid=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID'])
        );
        
        //Return the title contents
        return sprintf('<span id="cell%2$s">%1$s</span> <span style="color:silver">(id:%2$s)</span>',
            /*$1%s*/ $item['post_title'],
            /*$2%s*/ $item['ID']
        );
    }
	
	function column_tx($item) {
		$tx = sprintf('<input type="text" name="tx%s" class="new_desc" /><a id="%1$s" class="grabar_todos vacio" href="#" >Save in all</a>',$item['ID']);
		return $tx;
	}

	function column_date($item) {
		$date = sprintf('%s',$item['post_date']);
		return $date;
	}

	function column_qty($item) {
		$qty = sprintf('%s',$item['attached']);
		return $qty;
	}
    
    function get_columns(){
	$columns = array(
		'post_title' => 'Title',
		'name'       => 'Category',
		'date'       => 'Date',
		'qty'        => 'Attached images',
		'tx'         => 'New title'
		);
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'post_title' => array('post_title',true),     //true means its already sorted
            'name'  => array('name',false),
	    'date'  => array('post_date',false)
        );
        return $sortable_columns;
    }

    function prepare_items() {

        $per_page = 10; // qty of images to show
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

		function traigo($tipo) {
			global $wpdb;
			$querystr = "SELECT p.ID, p.post_title, p.post_date, wp_terms.name, (SELECT COUNT(pp.ID) FROM wp_posts pp WHERE pp.post_type = 'attachment' AND pp.post_parent = p.ID) as attached
						 FROM wp_posts p
						 LEFT JOIN wp_term_relationships ON (p.ID =wp_term_relationships.object_id)
						 LEFT JOIN wp_term_taxonomy ON (wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id)
						 LEFT JOIN wp_terms ON(wp_terms.term_id =wp_term_taxonomy.term_id)
						 WHERE p.post_status='publish'
						";
			if ($tipo == 'post') { $querystr = $querystr." AND $wpdb->term_taxonomy.taxonomy = 'category'"; }
			$data = $wpdb->get_results( $querystr, ARRAY_A );
			return $data;
		}
			$tipo = "post";
        $data = traigo($tipo); 

        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');

        $current_page = $this->get_pagenum();

        $total_items = count($data);

        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        $this->items = $data;

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                    
            'total_pages' => ceil($total_items/$per_page)
        ) );
    }
    
}

function tt_add_menu_items(){
	$icon = plugin_dir_url( __FILE__ ); $icon = $icon.'/pictures.png';
    add_menu_page('Attached images title editor', 'Images title editor', 'activate_plugins', 'tt_list_test', 'tt_render_list_page', $icon);
} add_action('admin_menu', 'tt_add_menu_items');

function tt_render_list_page(){
	$css =  plugin_dir_url( __FILE__ ).'style.css' ;
    wp_enqueue_style( "mass editor",  $css );
    		$testListTable = new TT_List_Table();
	
    $testListTable->prepare_items();
    
    ?>
    <div class="wrap">
        
        <img src="<?php echo plugin_dir_url( __FILE__ ).'/pictures.png' ?>" class="icon16" />
        <h2>Attached images title editor</h2>
        <div id="textbox" style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p>This plugin is here to help you edit images at once!</p>
        </div>

            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />

            <?php $testListTable->display(); ?>
        </form>
    </div>
	<script type="text/javascript">
	jQuery(".vacio").click(function() {
		var h = jQuery(this).prev().attr('value');
		var k = jQuery("#new_desc").attr('value');
		if (h == "") {
			if (confirm("The input is empty, do you really want to continue?")) { 
				return true;
			} else { return false; }
		} else if (h == null) {
			if (k == "") {
					if (confirm("The input is empty, do you really want to continue?")) { 
						return true;
					} else { return false; }
			}
		}
	});
	function willdo(desc) {
		var celdas = jQuery(".cell");
		var cant = celdas.length;
		var i = 0;
		var k = false;
		while (i < cant && k==false) {
			
			if (celdas.eq(i).html()!=desc) {
				k = true;
			}
			i++;
		}
		return k;
	}
	jQuery(".grabar_todos").click(function() {
			var pepe = jQuery(this);
			   pepe.html("<p>saving...</p>");
				var t = pepe.attr('id');
				var h = pepe.prev().attr('value');
				var dataString = 'id='+ t + '&ndesc=' + h;
		  	   jQuery.ajax({
		    	    type: "POST",
		    	    dataType: 'html',
		    	    url: "../wp-content/plugins/attached_images_title_editor/update.php",
		    	    data: dataString,
		    	    success: function(data) {
				 jQuery("#"+t).html("Save in all");
				 pepe.next().remove();
		 		 var anterior = jQuery("#cell"+t).html();
				 jQuery("#"+t)
		 jQuery("<img class='checkmark' src='<?php echo plugin_dir_url( __FILE__ ).'/ok.png' ?>' />").insertAfter(jQuery("#"+t))
		      		 .hide()
		      		 .fadeIn(1500);
		    	   	}
		  	   });
	});
	jQuery("#doaction3").click(function() {	
		if (!willdo(jQuery("#new_desc").attr('value'))) {			
			alert ("The images already have that value."); 
			return false;		
		}
	});

	</script>
    <?php
}
