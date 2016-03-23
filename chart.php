<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script src="Highcharts-2.2.2/js/highcharts.js" type="text/javascript"></script>
<script type="text/javascript">
function getParameterByName(name, def) {
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.search);
  if(results == null)
    return def;
  else {
    console.log(name,"=",decodeURIComponent(results[1].replace(/\+/g, " ")));
    return decodeURIComponent(results[1].replace(/\+/g, " "));
  }
}
function loadDaily(cb) {
  var from = getParameterByName("from");
  if(typeof from === "undefined") {
    from = new Date();
    from.setDate(from.getDate() - 6);
    from = (from.getYear() + 1900) + "-" + (from.getMonth() + 1) + "-" + from.getDate();
  }
  var to = getParameterByName("to");

  jQuery.ajax({
    url: "https://p2k12.bitraf.no/checkins-trygvis.php?from=" + from + (to != null ? to : ""),
    success: function(data, textStatus, xhr) {
      var columns = []
      var series = []
      jQuery.each(data["collection"].items, function(i, item) {
        jQuery.each(item.data, function(i, item) {
          if(item.name === "checkins") {
            series.push(parseInt(item.value));
          }
          if(item.name === "date") {
            columns.push(item.value);
          }
        });
      });
      cb(columns, series);
    }
  });
}

function dailyCategories() {
  var n = new Date();
  var date = n.getDate();
  var categories = [];

  for(var i = 0; i < 7; i++) {
    var then = new Date();
    then.setDate(date - 6 + i);
    categories[i] = "" + then.getDate();
  }

//  console.log("xCategories", categories);
  return categories;
}

function buildOptions(categories, series) {
  return {
    chart: {
      animation: false,
      renderTo: 'container',
      type: 'line',
//      marginTop: 0, marginLeft: 0, marginRight: 0, marginBottom: 0,
//      spacingTop: 0, spacingLeft: 0, spacingRight: 0, spacingBottom: 0,
      backgroundColor: getParameterByName("backgroundColor", null),
      plotBackgroundColor: getParameterByName("plotBackgroundColor", null)
    },
    credits: {
      enabled: false
    },
    title: {
      text: null
    },
    xAxis: {
      categories: categories,
      labels: {
        enabled: false
      }
    },
    yAxis: {
      title: {
        text: null
      },
      plotLines: [{
        value: 0,
        width: 1,
        color: '#808080'
      }],
      labels: {
        enabled: true
      }
    },
    tooltip: {
      formatter: function() {
          // return '<b>'+ this.series.name +'</b><br/>'+ this.x +': '+ this.y +'Â°C';
          return 'Checkins: ' + this.y;
      }
    },
    legend: {
      enabled: false
    },
    series: [{name: 'Checkins', data: series}]
  };
}
</script>
<script type="text/javascript">
jQuery(document).ready(function() {
  loadDaily(function(columns, series) {
    var options = buildOptions(columns, series);
    console.log("options", options);
    chart = new Highcharts.Chart(options);
  })
});
</script>
</head>
<body>
<div id="container" style="width: 100%; height: 100%">
Loading data...
</div>
</body>
</html>
