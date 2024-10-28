<?php
require( '../../../wp-load.php' );
global $wpdb;
$id   = $_POST['id'];
if (!isset($_POST['ndesc'])) {
$desc = $_POST['desc'];
$querystr = "UPDATE $wpdb->posts SET $wpdb->posts.post_title = '".$desc."' WHERE $wpdb->posts.ID = ".$id;
} else {
$desc = $_POST['ndesc'];
$querystr = "UPDATE wp_posts SET wp_posts.post_title = '$desc' WHERE wp_posts.post_parent = $id AND post_type='attachment'";
}
if ($wpdb->query($wpdb->prepare($querystr,'wp_posts.post_title',$id))) {
	echo "correcto!";
} else {
	$wpdb->show_errors();
	echo $wpdb->print_error();
}
?>
