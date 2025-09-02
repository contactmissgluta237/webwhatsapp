<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Mes Produits</h1>
        <p class="mt-2 text-sm text-gray-600">Gérez vos produits pour votre assistant IA.</p>
    </div>

    <div class="mb-4">
        <button 
            wire:click="openCreateForm"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
        >
            Nouveau produit
        </button>
    </div>

    @if($showForm)
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ $editingProduct ? 'Modifier le produit' : 'Nouveau produit' }}
            </h3>

            <form wire:submit="save">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">
                            Titre
                        </label>
                        <input 
                            wire:model="title" 
                            type="text" 
                            id="title"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">
                            Prix (FCFA)
                        </label>
                        <input 
                            wire:model="price" 
                            type="number" 
                            id="price"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        @error('price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea 
                        wire:model="description" 
                        id="description"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    ></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mt-6">
                    <button 
                        type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        {{ $editingProduct ? 'Mettre à jour' : 'Créer' }}
                    </button>
                    <button 
                        type="button"
                        wire:click="resetForm"
                        class="ml-3 inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($this->products as $product)
                <li class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h4 class="text-lg font-medium text-gray-900">{{ $product->title }}</h4>
                            <p class="text-sm text-gray-600">{{ $product->description }}</p>
                            <p class="text-sm font-medium text-gray-900">{{ number_format($product->price) }} FCFA</p>
                            <p class="text-xs text-gray-500">
                                Statut: 
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $product->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                wire:click="edit({{ $product->id }})"
                                class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Modifier
                            </button>
                            <button 
                                wire:click="toggleStatus({{ $product->id }})"
                                class="text-yellow-600 hover:text-yellow-900 text-sm font-medium"
                            >
                                {{ $product->is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                            <button 
                                wire:click="delete({{ $product->id }})"
                                wire:confirm="Êtes-vous sûr de vouloir supprimer ce produit ?"
                                class="text-red-600 hover:text-red-900 text-sm font-medium"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-6 py-4 text-center text-gray-500">
                    Aucun produit trouvé. Créez votre premier produit !
                </li>
            @endforelse
        </ul>
    </div>
</div>