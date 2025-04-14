@extends('layouts.admin')


@section('content')
    <div class="col-12 ">
        <div style="padding-top: 10px">
            <h3>Classement</h4>
        </div>
        <div class="card shadow">
            <div class="card-body ">



                <div class="table-responsive">
                    <livewire:order-filter />

                </div>
            </div>
        </div>
    </div>
@endsection
