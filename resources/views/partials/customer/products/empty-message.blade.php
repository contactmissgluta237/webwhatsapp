<div class="card">
    <div class="card-body">
        <div class="text-center py-5">
            <i class="la la-cubes text-whatsapp" style="font-size: 4rem;"></i>
            <h5 class="mt-3">{{ __('Aucun produit pour le moment') }}</h5>
            <p>{{ __('Créez votre premier produit pour le proposer à vos clients.') }}</p>
            <a href="{{ route('customer.products.create') }}" class="btn btn-whatsapp">
                <i class="la la-plus"></i> {{ __('Créer mon premier produit') }}
            </a>
        </div>
    </div>
</div>
