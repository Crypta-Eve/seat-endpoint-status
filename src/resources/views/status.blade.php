@extends('web::layouts.grids.12')

@section('title', 'ESI Endpoint Status')
@section('page_header', 'Endpoint Status')
@section('page_description', 'ESI Endpoint Status')


@section('full')

    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">ESI Endpoint Status</h3>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Endpoint</th>
                        <th scope="col">ESI _latest Version</th>
                        <th scope="col">SeAT Version</th>
                        <th scope="col">SeAT Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($endpoints as $endpoint => $edata)
                        <tr class="{{ isset($edata['latest_version']) && isset($edata['seat_version']) ? $edata['seat_version'] != $edata['latest_version'] ? 'table-danger' : 'table-success' : 'table-warning' }}">
                            <th scope="row">{{ $endpoint }}</th>
                            <td>{{ isset($edata['latest_version']) ? $edata['latest_version'] : "N/A" }}</td>
                            <td>{{ isset($edata['seat_version']) ? $edata['seat_version'] : "Not Found" }}</td>
                            <td>{{ isset($edata['seat_status']) ? $edata['seat_status'] : "Not Found" }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
@stop
