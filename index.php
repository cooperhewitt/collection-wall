<?php

// Include the SDK
require_once 'awssdk/sdk.class.php';
$sdb = new AmazonSDB();
$domain = 'collection-wall-beta';
$next_token = null;

$page = 0;

$page = $_GET['page'];


for($i=0; $i <= $page; $i++) {
if ($next_token)
{
	$results = $sdb->select("SELECT * FROM `{$domain}`" , array(
		'NextToken' => $next_token,
	) );
}
else
{
	$results = $sdb->select("SELECT * FROM `{$domain}`");
}


$next_token = isset($results->body->SelectResult->NextToken)
	        ? (string) $results->body->SelectResult->NextToken
	        : null;
}

$items = $results->body->Item();

$data = reorganize_data($items);

$html = generate_html_table($data);


function reorganize_data($items)
{
	// Collect rows and columns
	$rows = array();
	$columns = array();

	// Loop through each of the items
	foreach ($items as $item)
	{
		// Let's append to a new row
		$row = array();
		$row['id'] = (string) $item->Name;

		// Loop through the item's attributes
		foreach ($item->Attribute as $attribute)
		{
			// Store the column name
			$column_name = (string) $attribute->Name;

			// If it doesn't exist yet, create it.
			if (!isset($row[$column_name]))
			{
				$row[$column_name] = array();
			}

			// Append the new value to any existing values
			// (Remember: Entries can have multiple values)
			$row[$column_name][] = (string) $attribute->Value;
			natcasesort($row[$column_name]);

			// If we've not yet collected this column name, add it.
			if (!in_array($column_name, $columns, true))
			{
				$columns[] = $column_name;
			}
		}

		// Append the row we created to the list of rows
		$rows[] = $row;
	}

	// Return both
	return array(
		'columns' => $columns,
		'rows' => $rows,
	);
}

function generate_html_table($data)
{
	// Retrieve row/column data
	$columns = $data['columns'];
	$rows = $data['rows'];

	$output = '';

	// Loop through the rows
	foreach ($rows as $row)
	{
		$output .= '<div class="element">' . PHP_EOL;
		$output .= '<img src="http://data.cooperhewitt.org/media/350/' . $row[$columns[1]][0] . '.jpg" />';
		$output .= '<p class="weight"><a href="http://collection.cooperhewitt.org/view/objects/asitem/id/' . $row['id'] . '">' . $row[$columns[0]][0] . '</a></p>' . PHP_EOL;
		$output .= '</div>' . PHP_EOL;
		$output .= PHP_EOL;
	}

	return $output;
}


?>



<!doctype html>
<html lang="en">
<head>
  
  <meta charset="utf-8" />
  <title>Collection Wall Alpha | Smithsonian Cooper-Hewitt, National Design Museum in New York</title>
  
  <!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
  
  <link rel="stylesheet" href="css/style.css" />
  
  <!-- scripts at bottom of page -->


</head>
<body id="top" class="homepage ">
  
  
  <section id="content">
<img src="images/logo-black.jpg" style="float:right;">
  <h1 style="padding-bottom:20px;padding-top:20px;"><a href="/">Collection Wall Alpha v0.1.1</a></h1>
	      



  <div id="container" class="clearfix clickable infinite-scrolling">
    
      	<?php echo $html; ?>
		<p id="back-top">
			<a href="#top"><span></span>Back to Top</a>
		</p>		
          
  </div>  

	
  <nav id="page_nav">
	<a href="?page=<?php echo $page+1 ?>">Next Page</a>
  </nav>
 
  <script src="js/jquery-1.7.1.min.js"></script>
  <script src="jquery.isotope.min.js"></script>
  <script src="js/nextPage.js"></script>
  <script src="js/jquery.infinitescroll.min.js"></script>

<script>

$(function(){
    
      var $container = $('#container');

      $('#append a').click(function(){
        var $newEls = $( fakeElement.getGroup() );
        $container.append( $newEls ).isotope( 'appended', $newEls );

        return false;
      });
	
      $container.isotope({
        masonry: {
          columnWidth: 120
        },
        sortBy: 'number',
        getSortData: {
          number: function( $elem ) {
            var number = $elem.hasClass('element') ? 
              $elem.find('.number').text() :
              $elem.attr('data-number');
            return parseInt( number, 10 );
          },
          alphabetical: function( $elem ) {
            var name = $elem.find('.name'),
                itemText = name.length ? name : $elem;
            return itemText.text();
          }
        }
      });

      var $optionSets = $('#options .option-set'),
          $optionLinks = $optionSets.find('a');

      $optionLinks.click(function(){
        var $this = $(this);
        // don't proceed if already selected
        if ( $this.hasClass('selected') ) {
          return false;
        }
        var $optionSet = $this.parents('.option-set');
        $optionSet.find('.selected').removeClass('selected');
        $this.addClass('selected');
  
        // make option object dynamically, i.e. { filter: '.my-filter-class' }
        var options = {},
            key = $optionSet.attr('data-option-key'),
            value = $this.attr('data-option-value');
        // parse 'false' as false boolean
        value = value === 'false' ? false : value;
        options[ key ] = value;
        if ( key === 'layoutMode' && typeof changeLayoutMode === 'function' ) {
          // changes in layout modes need extra logic
          changeLayoutMode( $this, options )
        } else {
          // otherwise, apply new options
          $container.isotope( options );
        }
        
        return false;
      });

	 // change size of clicked element
      $container.delegate( '.element', 'click', function(){
        $(this).toggleClass('large');
        $container.isotope('reLayout');
      });

      // toggle variable sizes of all elements
      $('#toggle-sizes').find('a').click(function(){
        $container
          .toggleClass('variable-sizes')
          .isotope('reLayout');
        return false;
      });


	$container.infinitescroll({
	        navSelector  : '#page_nav',    // selector for the paged navigation 
	        nextSelector : '#page_nav a',  // selector for the NEXT link (to page 2)
	        itemSelector : '.element',     // selector for all items you'll retrieve
	        loading: {
	            finishedMsg: 'No more pages to load.',
	            img: 'http://i.imgur.com/qkKy8.gif'
	          }
	        },
	        // call Isotope as a callback
	        function( newElements ) {
	          $container.isotope( 'appended', $( newElements ) ); 
	        }
	      );


});
	
  </script>

<script>
$(document).ready(function(){

	// hide #back-top first
	$("#back-top").hide();

	// fade in #back-top
	$(function () {
		$(window).scroll(function () {
			if ($(this).scrollTop() > 100) {
				$('#back-top').fadeIn();
			} else {
				$('#back-top').fadeOut();
			}
		});

		// scroll body to 0px on click
		$('#back-top a').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 800);
			return false;
		});
	});

});
</script>

    
    <footer>
  Powered by <a href="http://isotope.metafizzy.co/">Isotope</a> and several hundred chocolate covered coffee beans.</a>
    </footer>
    
  </section> <!-- #content -->
  
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-21347959-1']);
  _gaq.push(['_setDomainName', 'cooperhewitt.org']);
  _gaq.push(['_trackPageview']);
  _gaq.push(['_trackPageLoadTime']); 
  _gaq.push(['_setCampaignCookieTimeout', 172800000]);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

<script type="text/javascript">
		if(typeof jQuery != 'function'){
		var script = '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"><\/script>';
		document.write(script);	
	}

</script>


<script type="text/javascript">

	$(document).ready(function(){

		$('a').mouseup(function(){
  			href = $(this).attr('href');
  			href_lower = href.toLowerCase(); 
  			if(href_lower.substr(-3) == "pdf" || href_lower.substr(-3) == "xls" || href_lower.substr(-3) == "doc" ||
  			   href_lower.substr(-3) == "mp3" || href_lower.substr(-3) == "mp4" || href_lower.substr(-3) == "flv" ||
  			   href_lower.substr(-3) == "txt" || href_lower.substr(-3) == "csv" || href_lower.substr(-3) == "zip") {
   				_gaq.push(['_trackEvent', 'Downloads', href_lower.substr(-3), href]);
  			} 
  			if(href_lower.substr(0, 4) == "http") {
   				var domain = document.domain.replace("www.",'');
  				if(href_lower.indexOf(domain) == -1){
				href = href.replace("http://",'');
				href = href.replace("https://",'');
 					_gaq.push(['_trackEvent', 'Outbound Traffic', href]);
   				}
  			}
 		});
	});
</script>
</body>
</html>