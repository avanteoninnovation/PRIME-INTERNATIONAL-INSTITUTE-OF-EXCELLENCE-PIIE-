@extends('warden.navigation')
@section('content')
    @php
        // Hostel-related statistics
        $total_hostels = DB::table('hostels')
            ->where('school_id', auth()->user()->school_id)
            ->count();
        $total_rooms = DB::table('hostel_rooms')
            ->where('school_id', auth()->user()->school_id)
            ->count();
        $total_allocations = DB::table('hostel_room_allocations')
            ->where('school_id', auth()->user()->school_id)
            ->where('status', 1)
            ->count();
        $pending_applications = DB::table('hostel_applications')
            ->where('school_id', auth()->user()->school_id)
            ->where('status', 0)
            ->count();

        // Get available beds
        $available_beds =
            DB::table('hostel_rooms')
                ->where('school_id', auth()->user()->school_id)
                ->sum('capacity') -
            DB::table('hostel_rooms')
                ->where('school_id', auth()->user()->school_id)
                ->sum('occupied');

        // Hostel occupancy data for chart
        $hostel_occupancy_data = [];
        $hostels = DB::table('hostels')
            ->where('school_id', auth()->user()->school_id)
            ->get();

        foreach ($hostels as $hostel) {
            $occupied_beds = DB::table('hostel_room_allocations')
                ->join('hostel_rooms', 'hostel_room_allocations.room_id', '=', 'hostel_rooms.id')
                ->where('hostel_rooms.hostel_id', $hostel->id)
                ->where('hostel_room_allocations.status', 1)
                ->where('hostel_room_allocations.school_id', auth()->user()->school_id)
                ->count();

            $hostel_occupancy_data[] = [
                'hostel_name' => $hostel->name,
                'occupied_beds' => $occupied_beds,
            ];
        }
    @endphp

    <!-- Mani section header and breadcrumb -->
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Dashboard') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Dashboard') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Alerts -->
    <div class="row">
        <div class="col-12">
            <div class="eSection-dashboardItems">
                <div class="row flex-wrap">
                    <div class="col-lg-12">
                        <div class="dashboard_ShortListItem">
                            <h4 class="text-dark">{{ auth()->user()->name }}</h4>
                            <p>Welcome, to {{ DB::table('schools')->where('id', auth()->user()->school_id)->value('title') }}</p>
                        </div>
                    </div>
                    <!-- Dashboard Short Details -->
                    <div class="col-lg-6">
                        <div class="dashboard_ShortListItems">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="dashboard_ShortListItem">
                                        <div class="dsHeader d-flex justify-content-between align-items-center">
                                            <h5 class="title">{{ get_phrase('Hostels') }}</h5>
                                        </div>
                                        <div class="dsBody d-flex justify-content-between align-items-center">
                                            <div class="ds_item_details">
                                                <h4 class="total_no">{{ $total_hostels }}</h4>
                                                <p class="total_info">{{ get_phrase('Total Hostels') }}</p>
                                            </div>
                                            <div class="ds_item_icon">
                                                <img src="{{ asset('assets/images/Student_icon.png') }}" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rooms -->
                                <div class="col-md-6">
                                    <div class="dashboard_ShortListItem">
                                        <div class="dsHeader d-flex justify-content-between align-items-center">
                                            <h5 class="title">{{ get_phrase('Rooms') }}</h5>
                                        </div>
                                        <div class="dsBody d-flex justify-content-between align-items-center">
                                            <div class="ds_item_details">
                                                <h4 class="total_no">{{ $total_rooms }}</h4>
                                                <p class="total_info">{{ get_phrase('Total Rooms') }}</p>
                                            </div>
                                            <div class="ds_item_icon">
                                                <img src="{{ asset('assets/images/Teacher_icon.png') }}" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Allocations -->
                                <div class="col-md-6">
                                    <div class="dashboard_ShortListItem">
                                        <div class="dsHeader d-flex justify-content-between align-items-center">
                                            <h5 class="title">{{ get_phrase('Allocations') }}</h5>
                                        </div>
                                        <div class="dsBody d-flex justify-content-between align-items-center">
                                            <div class="ds_item_details">
                                                <h4 class="total_no">{{ $total_allocations }}</h4>
                                                <p class="total_info">{{ get_phrase('Active Allocations') }}</p>
                                            </div>
                                            <div class="ds_item_icon">
                                                <img src="{{ asset('assets/images/Parents_icon.png') }}" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Available Beds -->
                                <div class="col-md-6">
                                    <div class="dashboard_ShortListItem">
                                        <div class="dsHeader d-flex justify-content-between align-items-center">
                                            <h5 class="title">{{ get_phrase('Available Beds') }}</h5>
                                        </div>
                                        <div class="dsBody d-flex justify-content-between align-items-center">
                                            <div class="ds_item_details">
                                                <h4 class="total_no">{{ $available_beds }}</h4>
                                                <p class="total_info">{{ get_phrase('Beds Available') }}</p>
                                            </div>
                                            <div class="ds_item_icon">
                                                <img src="{{ asset('assets/images/Staff_icon.png') }}" alt="" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- Imcome Report -->

                    <!-- Upcoming Events -->
                    <div class="col-md-6 ms-auto">
                        <div class="dashboard_report dashboard_upcoming_events">
                            <div class="ds_report_header d-flex justify-content-between align-items-start">
                                <div class="ds_report_left">
                                    <h4 class="title">{{ get_phrase('Upcoming Events') }}</h4>
                                </div>

                            </div>
                            <div class="ds_report_list pt-38">
                                <ul class="upcoming_events_items d-flex flex-column">

                                    @php
                                        $upcoming_events = DB::table('frontend_events')
                                            ->where('school_id', auth()->user()->school_id)
                                            ->where('timestamp', '>', time())
                                            ->where('status', 1)
                                            ->take(3)
                                            ->orderBy('id', 'DESC')
                                            ->get();
                                    @endphp
                                    @foreach ($upcoming_events as $upcoming_event)
                                        <li>
                                            <div class="upcoming_events_item d-flex justify-content-between align-items-start">
                                                <div class="events_info">
                                                    <a href="#" class="title">{{ $upcoming_event->title }}</a>
                                                    <p class="date">{{ date('D, M d Y', $upcoming_event->timestamp) }}</p>
                                                </div>

                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="text-end">
                                    <a href="{{ route('warden.events.list') }}" class="all_report_btn_2">{{ get_phrase('See all') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Container -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ get_phrase('Hostel Occupancy Overview') }}</h5>
                </div>
                <div class="card-body">
                    <div id="chartdiv" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resources -->
    <script src="{{ asset('assets/amchart/index.js') }}"></script>
    <script src="{{ asset('assets/amchart/xy.js') }}"></script>
    <script src="{{ asset('assets/amchart/animated.js') }}"></script>

    <!-- Chart code -->
    <script>
        "use strict";

        am5.ready(function() {

            // Create root element
            var root = am5.Root.new("chartdiv");

            // Set themes
            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            // Create chart
            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true,
                panY: true,
                wheelX: "panX",
                wheelY: "zoomX",
                pinchZoomX: true
            }));

            // Add cursor
            var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
            cursor.lineY.set("visible", false);

            // Create axes
            var xRenderer = am5xy.AxisRendererX.new(root, {
                minGridDistance: 30
            });
            xRenderer.labels.template.setAll({
                rotation: -90,
                centerY: am5.p50,
                centerX: am5.p100,
                paddingRight: 15
            });

            var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                maxDeviation: 0.3,
                categoryField: "hostel_name",
                renderer: xRenderer,
                tooltip: am5.Tooltip.new(root, {})
            }));

            var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                maxDeviation: 0.3,
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            // Create series
            var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                name: "Series 1",
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "occupied_beds",
                sequencedInterpolation: true,
                categoryXField: "hostel_name",
                tooltip: am5.Tooltip.new(root, {
                    labelText: "Occupied Beds: {valueY}"
                })
            }));

            series.columns.template.setAll({
                cornerRadiusTL: 5,
                cornerRadiusTR: 5
            });
            series.columns.template.adapters.add("fill", function(fill, target) {
                return chart.get("colors").getIndex(series.columns.indexOf(target));
            });

            series.columns.template.adapters.add("stroke", function(stroke, target) {
                return chart.get("colors").getIndex(series.columns.indexOf(target));
            });

            // Set data - Hostel occupancy data
            var data = <?php echo json_encode($hostel_occupancy_data); ?>;

            xAxis.data.setAll(data);
            series.data.setAll(data);

            // Make stuff animate on load
            series.appear(1000);
            chart.appear(1000, 100);

        }); // end am5.ready()
    </script>
@endsection
