<!-- Default box -->
<div class="card">
   <div class="card-header">
       <div class="user-block">
            <img class="img-circle img-bordered-sm" src="{{ @$business->profile_image }}" alt="user image">
            <span class="username">
                <a href="#">{{ $business->name }}</a>
            </span>
        </div>
   </div>
   <div class="card-body">
      <div class="row">
         <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $getContest }}</h3>
                            <p>Total Contests</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-bag"></i>
                        </div>
                        <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>53<sup style="font-size: 20px">%</sup></h3>
                            <p>Total Events</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-stats-bars"></i>
                        </div>
                        <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $getOffers }}</h3>
                            <p>Total Offers</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-person-add"></i>
                        </div>
                        <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
                <div class="col-lg-3 col-6">
                    <!-- small box -->
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>{{ $getRewards }}</h3>
                            <p>Total Rewards</p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-pie-graph"></i>
                        </div>
                        <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <!-- ./col -->
        </div>
        
            <div class="row">
               <div class="col-12">
                    <div class="card-body">
                        <div>
                            <div class="btn-group w-100 mb-2">
                                <a class="btn btn-primary active" href="javascript:void(0)" data-filter="all">Images</a>
                            </div>
                        </div>
                        <div>
                            <div class="filter-container p-0 row">
                                @foreach ($business['businessImage'] as $key => $image)
                                    <div class="filtr-item col-sm-4" data-category="1" data-sort="white sample">
                                        <a href="{{ $image->photo }}" data-toggle="lightbox" data-title="Business Image">
                                            <img src="{{ $image->photo }}" class="img-fluid mb-2" alt="white sample"/>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
               </div>
            </div>

         </div>
         <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
            <h5 class="text-muted">Owner Details</h5>
            <ul class="list-unstyled">
               <li>
                  <a href="" class="btn-link text-secondary"><i class="fa fa-user" aria-hidden="true"></i> {{$business['businessProfile']->owner_name}} </a>
               </li>
               <li>
                  <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-envelope"></i> {{$business['businessProfile']->owner_email}} </a>
               </li>
               <li>
                  <a href="" class="btn-link text-secondary"><i class="fa fa-phone-square" aria-hidden="true"></i> {{$business['businessProfile']->owner_phone}} </a>
               </li>
            </ul>
            <div class="card card-primary">
               <div class="card-body">
                  <strong><i class="fa fa-phone-square"></i>Business Phone</strong>
                  <p class="text-muted">
                     {{ $business->phone }}
                  </p>
                  <hr>
                  <strong><i class="fa fa-envelope" aria-hidden="true"></i>Business Email</strong>
                  <p class="text-muted">{{ $business->email }}</p>
                  <hr>
                  <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>
                  <p class="text-muted">{{ $business['businessAddress']->address_line1 }}</p>
                  <hr>
                  <strong><i class="far fa-file-alt mr-1"></i>Status</strong>
                  <span class="badge @if($business->status == "active") bg-primary @else bg-danger @endif" style="text-transform: capitalize;">{{  $business->status }}</span>
                  <hr>
                  <strong><i class="fa fa-link" aria-hidden="true"></i> Website</strong>
                  <p class="text-muted">{{ $business['businessProfile']->website ?? 'N/A'}}</p>
                  <hr>
                  <strong><i class="fa fa-hand-pointer" aria-hidden="true"></i> Points Per CheckiIn</strong>
                  <p class="text-muted">{{ $business['businessProfile']->points_per_checkin ?? '0'}}</p>
                  <hr>
                  <strong><i class="fa fa-sign-in" aria-hidden="true"></i> Last Login</strong>
                  <p class="text-muted">
                    @if ($business->last_login_at)
                        {{ \Carbon\Carbon::parse($business->last_login_at)->format('d M Y, h:i A') }}
                    @else
                        <span class="badge bg-danger">Never Logged In</span>
                    @endif
                  </p>
               </div>
            </div>
         </div>
      </div>
      <!-- Business Hours Table -->
      <div id="business-hours-section">
         <h4>Business Hours</h4>
         <table class="table">
            <thead>
               <tr>
                  <th>Day</th>
                  <th class="text-center">Open Time</th>
                  <th class="text-center">Close Time</th>
                  <th class="text-center">Closed?</th>
               </tr>
            </thead>
            <tbody>
               @php
               $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
               @endphp
               @foreach($days as $key => $day)
               @php
               // Find existing business hour entry for this day
               $existingHour = $business->businessHours->where('day_of_week', strtolower($day))->first();
               $isClosed = $existingHour ? $existingHour->is_closed : 0;
               @endphp
               <tr>
                  <td>{{ $day }}</td>
                  <td>
                     <input type="time" name="business_hours[{{ $key }}][open_time]" class="form-control open-time" value="{{ $isClosed ? '' : ($existingHour ? $existingHour->open_time : '') }}" {{ $isClosed ? 'disabled' : '' }} disabled>
                  </td>
                  <td>
                     <input type="time" name="business_hours[{{ $key }}][close_time]" class="form-control close-time" value="{{ $isClosed ? '' : ($existingHour ? $existingHour->close_time : '') }}" {{ $isClosed ? 'disabled' : '' }} disabled>
                  </td>
                  <td>
                     <span class="badge {{ $isClosed ? 'badge-danger' : 'badge-primary' }}">
                     {{ $isClosed ? 'Yes' : 'No' }}
                     </span>
                  </td>
               </tr>
               @endforeach
            </tbody>
         </table>
      </div>
      <!-- End Business Hours Table -->
   </div>
   <!-- /.card-body -->
</div>
<!-- /.card -->