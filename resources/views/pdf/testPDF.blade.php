<!doctype html>
<html lang="{{ htmlLang() }}" @langrtl dir="rtl" @endlangrtl>
<head>
    <meta http-equiv="X-UA-Compatible">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test PDF</title>
    <meta name="description">
    <meta name="author">

    <link href="{{asset('/plugins/fancyuploder/fancy_fileupload.css')}}" rel="stylesheet" />
    <link href="{{ mix('css/backend.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" media="screen" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {'packages':['corechart']});
        google.charts.setOnLoadCallback(drawChart);
    
        function drawChart() {
          var data = google.visualization.arrayToDataTable([
            ['Year', 'Sales', 'Expenses'],
            ['2004',  1000,      400],
            ['2005',  1170,      460],
            ['2006',  660,       1120],
            ['2007',  1030,      540],
            ['2008',  1000,      400],
            ['2009',  1170,      460],
            ['2010',  660,       1120],
            ['2011',  1030,      540],
            ['2012',  1000,      400],
            ['2013',  1170,      460],
            ['2014',  660,       1120],
            ['2015',  1030,      540]
          ]);
    
          var options = {
            title: 'Company Performance',
            curveType: 'function',
            legend: { position: 'bottom' }
          };
    
          var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));
    
          chart.draw(data, options);
        }
      </script>
    <style>

* {
  font-family: Arial, Helvetica, sans-serif;
}

.container {
    max-width: 1200px;
}

.row {
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    margin-right: -.75rem;
    margin-left: -.75rem;
}

.card {
    position: relative;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-direction: column;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    position: relative;
    margin-bottom: 1.5rem;
    width: 100%;
    border: 0;
    box-shadow: 0 .15rem 1.75rem 0 rgba(196,205,224,.2);
    border-radius: 13px;
}

table, th, td {
  border: 1px solid black;
}

.table, .text-wrap table {
    width: 100%;
    max-width: 100%;
    margin-bottom: 1rem;
    border-collapse: collapse;
}


    </style>

    <script>

google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable([
          ['Year', 'Accordato', 'Utilizzato'],
          ['2004',  1000,      400],
          ['2005',  1170,      460],
          ['2006',  660,       1120],
          ['2007',  1030,      540]
        ]);

        var options = {
          title: 'Company Performance',
          curveType: 'function',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

        chart.draw(data, options);
      }
    </script>

    </head>
<body class="c-app">

    <div class="c-wrapper c-fixed-components">
        <div class="c-body">
            <main class="">
                <div class="container-fluid">
                    <div class="fade-in">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="container">
                                    <div class="row">
                                        <div class="col-md-12 col-lg-12">
                                            <div class="card">
                                                <div class="table-responsive">
                                                    <h3>Basic Table</h3>
                                                    <table class="table card-table table-vcenter text-nowrap mb-0">
                                                        <thead >
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Name</th>
                                                                <th>Position</th>
                                                                <th>Salary</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">1</th>
                                                                <td>Joan Powell</td>
                                                                <td>Associate Developer</td>
                                                                <td>$450,870</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">2</th>
                                                                <td>Gavin Gibson</td>
                                                                <td>Account manager</td>
                                                                <td>$230,540</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">3</th>
                                                                <td>Julian Kerr</td>
                                                                <td>Senior Javascript Developer</td>
                                                                <td>$55,300</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">4</th>
                                                                <td>Cedric Kelly</td>
                                                                <td>Accountant</td>
                                                                <td>$234,100</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">5</th>
                                                                <td>Samantha May</td>
                                                                <td>Junior Technical Author</td>
                                                                <td>$43,198</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="card overflow-hidden">
                                                <h3>Chart Test</h3>
                                                <div class="card-body">
                                                    <div style="width: 900px; height: 500px" id="curve_chart"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<footer>



</footer>


</body>
</html>


