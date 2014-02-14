
<h5 class="archive-title">Carte</h5>
<div id="map" style="height:380px;width:52%; margin: 50px; margin-left:24%; margin-right:24%;"></div>
<script type="text/javascript">
// create a map in the "map" div, set the view to a given place and zoom
var map = L.map('map').setView([47.9, 1.9], 6);

// add an OpenStreetMap tile layer
L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);
<?php echo getMarkerList(); ?>
</script>
