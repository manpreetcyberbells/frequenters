@extends('admin.layouts.master') 
@section('title', 'Reward Details') 
@section('content')

<div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Reedemption</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
              <li class="breadcrumb-item"><a href="{{ route('rewards.index') }}">Reward Listing</a></li>
              <li class="breadcrumb-item active">Reedemption</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            <div class="timeline">
                @if($reward->reedemtionHistory->isNotEmpty())
                    @foreach($reward->reedemtionHistory as $index => $history)
                        <div>
                            <img class="rounded-circle bg-green" 
                                style="width: 50px; height: 50px; object-fit: cover;" 
                                src="{{ $history->customer->profile_image ?? asset('default-profile.png') }}" 
                                alt="">

                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> {{ $history->created_at->diffForHumans() }}
                                </span>
                                <h3 class="timeline-header no-border">
                                    <a href="#">{{ $history->customer->name ?? 'N/A' }}</a> redeemed the reward
                                </h3>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <h5>No redemption history found.</h5>
                    </div>
                @endif
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
@endsection