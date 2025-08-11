<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="row">
                    @php
                        $managementColors = ['bg-dark', 'bg-secondary', 'bg-success'];
                    @endphp
                    @foreach($managementActions as $index => $action)
                    <div class="col-md-4 col-sm-12 mb-1">
                        <a href="{{ route($action['route']) }}" class="btn btn-block text-white {{ $managementColors[$index % count($managementColors)] }}" role="button">
                            <i class="{{ $action['icon'] }} fs-1 mr-1"></i>{{ $action['buttonText'] }}
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>