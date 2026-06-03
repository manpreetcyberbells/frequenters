<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="timeline">
                    <div class="time-label">
                        <span class="bg-info">Reward Earned History</span>
                    </div>
                    @foreach($interactionHistory as $index => $interaction)
                    <div>
                        <i class="fas fa-user bg-green"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($interaction->last_interaction_date)->diffForHumans() }}</span>
                            <h3 class="timeline-header no-border"><a href="#">{{ $interaction['business']->name }}</a> alloted {{ $interaction->points_allocated }} To <a href="#">{{ $interaction['customer']->name }}</a> For <span class="textCapital">{{ $interaction->interaction_type }}</span> </h3>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->
