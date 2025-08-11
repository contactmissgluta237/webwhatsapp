<div class="row m-1">
    <div class="col-12 collapse mb-4 p-0" id="collapseFilter">
        <div class="card">
            <div class="card-body">
                <form wire:submit.prevent="applyFilter">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="startDate" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="startDate" wire:model="startDate">
                        </div>
                        <div class="col-md-4">
                            <label for="endDate" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="endDate" wire:model="endDate">
                        </div>
                        <div class="col-md-4 d-flex justify-content-center mt-1">
                            <button type="submit" class="btn btn-info btn-sm">
                                <i class="ti ti-filter"></i> Appliquer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>