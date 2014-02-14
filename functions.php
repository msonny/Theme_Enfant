<?php

add_action('pre_get_posts', 'display_concerts');
add_action('pre_get_posts', 'display_actions');

function display_concerts($query) {

	if($query->is_front_page() && $query->is_main_query())
	{
		$query->set('post_type', array('concert'));

		//10 dernieres années
		$query->set('date_query', array('year' => getdate()['year']-10, 'compare' => '>='));

		//le lieu n'est pas spécifié
		//$query->set('meta_query', array(array('key'=>'wpcf-lieu', 'value' => false, 'type' => BOOLEAN)));

		//qui possède une image à la une
		//$query->set('meta_query', array(array('key'=>'_thumbnail_id', 'compare' => 'EXISTS')));

		return;
	}
}

function display_actions($query) {

	if($query->is_front_page() && $query->is_main_query())
	{
		$query->set('post_type', array('action'));

		//10 dernieres années
		$query->set('date_query', array('year' => getdate()['year']-10, 'compare' => '>='));

		//le lieu n'est pas spécifié
		//$query->set('meta_query', array(array('key'=>'wpcf-lieu', 'value' => false, 'type' => BOOLEAN)));

		//qui possède une image à la une
		//$query->set('meta_query', array(array('key'=>'_thumbnail_id', 'compare' => 'EXISTS')));

		return;
	}
}

function dashboard_widget_function() {

	$query = new WP_Query();
	$query->set('post_type', array('concert'));
	$query->set('meta_query', array(array('key'=>'wpcf-lieu', 'value' => false, 'type' => BOOLEAN)));
	$query->get_posts();
	$nb_lieux=$query->post_count;
	echo "Concert(s) sans lieux : ".$nb_lieux."<br/>";
}

function add_dashboard_widgets() {
	wp_add_dashboard_widget('dashboard_widget', 'Nombre de concert(s) sans lieux', 'dashboard_widget_function');
}

add_action('wp_dashboard_setup', 'add_dashboard_widgets');

//la fonction de geolocalization
function geolocalize($post_id) {
	if ( wp_is_post_revision( $post_id ) )
		return;

	$post = get_post($post_id);

	if ( !in_array( $post->post_type, array('concert') ) )
		return;

	$lieu = get_post_meta($post_id, 'wpcf-lieu', true);

	if(empty($lieu))
		return;

	$lat = get_post_meta($post_id, 'lat', true);

	if(empty($lat))
	{
		$address =  $lieu . ', France';
		$result = doGeolocation($address);

		if(false === $result)
			return;
		try{
			$location = $result[0]['geometry']['location'];
			add_post_meta($post_id, 'lat', $location["lat"]);
			add_post_meta($post_id, 'lng', $location["lng"]);

		}catch(Exception $e)
		{
			return;
		}
	}
}

add_action( 'save_post', 'geolocalize' );

//On recupere la geolocalization
function doGeolocation($address){

	$url = "http://maps.google.com/maps/api/geocode/json?sensor=false"."&address=" . urlencode($address);


	$proxy="wwwcache.univ-orleans.fr:3128";
	$context_array = array('http'=>array('proxy'=>$proxy,'request_fulluri'=>true));
	$context = stream_context_create($context_array);

	if($json = file_get_contents($url,0,$context)){
		$data = json_decode($json, TRUE);

		if($data['status']=="OK"){
			return $data['results'];
		}
	}
	return false;
}

//fonction pour le chargement du script
function load_scripts() {

	if(!is_post_type_archive('concert') && ! is_post_type_archive('action'))
		return;

	wp_register_script('leaflet-js','http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js');
	wp_enqueue_script('leaflet-js');
	wp_register_style('leaflet-css','http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css');
	wp_enqueue_style('leaflet-css');
}

add_action('wp_enqueue_scripts','load_scripts');


function getPostWithLatLon($post_type = "concert")
{
	global $wpdb;
	$query = "
	SELECT ID, post_title, p1.meta_value as lat, p2.meta_value as lng
	FROM wp_Archetsposts, wp_Archetspostmeta as p1, wp_Archetspostmeta as p2
	WHERE wp_Archetsposts.post_type = 'concert'
	AND p1.post_id = wp_Archetsposts.ID
	AND p2.post_id = wp_Archetsposts.ID
	AND p1.meta_key = 'lat'
	AND p2.meta_key = 'lng'";

	return $wpdb->get_results($query, OBJECT);

}

//La fonction makeListe
function getMarkerList($post_type = "concert")
{
	$results = getPostWithLatLon($post_type);
	$array = array();
	foreach($results as $result)
	{
		$array[] = "var marker_$result->ID = L.marker([".$result->lat.", ".$result->lng."]).addTo(map);";
		$array[] = "var popup_$result->ID = L.popup().setContent('Chargement...');";
		$array[] ="L.popup().setContent('".$result->post_title."');";
		$array[] ="marker_".$result->ID.".bindPopup('popup_".$result->post_title."');";
	}
	return implode(PHP_EOL, $array);
}


?>
