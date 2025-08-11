@extends('modern.layouts.master')

@section('title', 'Page Test - Modern Admin')

@section('content')
<div id="crypto-stats-3" class="row">
    <div class="col-xl-4 col-12">
        <div class="card crypto-card-3 pull-up">
            <div class="card-content">
                <div class="card-body pb-0">
                    <div class="row">
                        <div class="col-2">
                            <i class="la la-users warning font-large-2"></i>
                        </div>
                        <div class="col-5 pl-2">
                            <h4>Utilisateurs</h4>
                            <h6 class="text-muted">Total</h6>
                        </div>
                        <div class="col-5 text-right">
                            <h4>1,245</h4>
                            <h6 class="success darken-4">12% <i class="la la-arrow-up"></i></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-12">
        <div class="card crypto-card-3 pull-up">
            <div class="card-content">
                <div class="card-body pb-0">
                    <div class="row">
                        <div class="col-2">
                            <i class="la la-shopping-cart blue-grey lighten-1 font-large-2"></i>
                        </div>
                        <div class="col-5 pl-2">
                            <h4>Ventes</h4>
                            <h6 class="text-muted">Ce mois</h6>
                        </div>
                        <div class="col-5 text-right">
                            <h4>€25,847</h4>
                            <h6 class="success darken-4">8% <i class="la la-arrow-up"></i></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-12">
        <div class="card crypto-card-3 pull-up">
            <div class="card-content">
                <div class="card-body pb-0">
                    <div class="row">
                        <div class="col-2">
                            <i class="la la-line-chart info font-large-2"></i>
                        </div>
                        <div class="col-5 pl-2">
                            <h4>Revenus</h4>
                            <h6 class="text-muted">Aujourd'hui</h6>
                        </div>
                        <div class="col-5 text-right">
                            <h4>€3,247</h4>
                            <h6 class="danger">5% <i class="la la-arrow-down"></i></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Activité Récente</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li class="text-center mr-4">
                            <h6 class="text-muted">Transactions</h6>
                            <p class="text-bold-600 mb-0">1,247</p>
                        </li>
                        <li class="text-center mr-4">
                            <h6 class="text-muted">En attente</h6>
                            <p class="text-bold-600 mb-0">23</p>
                        </li>
                        <li class="text-center">
                            <h6 class="text-muted">Terminées</h6>
                            <p class="text-bold-600 mb-0">1,224</p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-content collapse show">
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#TXN001</td>
                                    <td>Recharge</td>
                                    <td class="success">+€500.00</td>
                                    <td><span class="badge badge-success">Terminé</span></td>
                                    <td>{{ now()->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td>#TXN002</td>
                                    <td>Retrait</td>
                                    <td class="danger">-€250.00</td>
                                    <td><span class="badge badge-warning">En attente</span></td>
                                    <td>{{ now()->subHour()->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td>#TXN003</td>
                                    <td>Recharge</td>
                                    <td class="success">+€1,200.00</td>
                                    <td><span class="badge badge-success">Terminé</span></td>
                                    <td>{{ now()->subHours(2)->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td>#TXN004</td>
                                    <td>Retrait</td>
                                    <td class="danger">-€75.00</td>
                                    <td><span class="badge badge-danger">Échoué</span></td>
                                    <td>{{ now()->subHours(3)->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Notifications Récentes</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
            </div>
            <div class="card-content">
                <div class="card-body">
                    <div class="media-list">
                        <div class="media">
                            <div class="media-left align-self-center">
                                <i class="ft-plus-square icon-bg-circle bg-cyan mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Nouvel utilisateur</h6>
                                <p class="notification-text font-small-3 text-muted">John Doe s'est inscrit</p>
                                <small class="text-muted">Il y a 5 minutes</small>
                            </div>
                        </div>
                        <div class="media mt-1">
                            <div class="media-left align-self-center">
                                <i class="ft-download icon-bg-circle bg-red bg-darken-1 mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Transaction réussie</h6>
                                <p class="notification-text font-small-3 text-muted">Recharge de €500 effectuée</p>
                                <small class="text-muted">Il y a 15 minutes</small>
                            </div>
                        </div>
                        <div class="media mt-1">
                            <div class="media-left align-self-center">
                                <i class="ft-alert-triangle icon-bg-circle bg-yellow bg-darken-3 mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Système</h6>
                                <p class="notification-text font-small-3 text-muted">Maintenance programmée</p>
                                <small class="text-muted">Il y a 1 heure</small>
                            </div>
                        </div>
                        <div class="media mt-1">
                            <div class="media-left align-self-center">
                                <i class="ft-check icon-bg-circle bg-green mr-2"></i>
                            </div>
                            <div class="media-body">
                                <h6 class="media-heading">Mise à jour</h6>
                                <p class="notification-text font-small-3 text-muted">Nouvelle version déployée</p>
                                <small class="text-muted">Il y a 2 heures</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Statistiques Détaillées</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <button class="btn btn-sm round btn-info btn-glow">
                        <i class="la la-download font-medium-1"></i> Exporter
                    </button>
                </div>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="table table-de mb-0">
                        <thead>
                            <tr>
                                <th>Période</th>
                                <th>Utilisateurs Actifs</th>
                                <th>Transactions</th>
                                <th>Montant Total</th>
                                <th>Taux de Réussite</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Aujourd'hui</td>
                                <td>245</td>
                                <td>1,247</td>
                                <td>€28,547.50</td>
                                <td><span class="badge badge-success">98.5%</span></td>
                                <td><button class="btn btn-sm round btn-outline-info">Détails</button></td>
                            </tr>
                            <tr>
                                <td>Hier</td>
                                <td>198</td>
                                <td>987</td>
                                <td>€22,143.75</td>
                                <td><span class="badge badge-success">97.2%</span></td>
                                <td><button class="btn btn-sm round btn-outline-info">Détails</button></td>
                            </tr>
                            <tr>
                                <td>Cette semaine</td>
                                <td>1,456</td>
                                <td>8,234</td>
                                <td>€187,652.25</td>
                                <td><span class="badge badge-warning">96.8%</span></td>
                                <td><button class="btn btn-sm round btn-outline-info">Détails</button></td>
                            </tr>
                            <tr>
                                <td>Ce mois</td>
                                <td>5,847</td>
                                <td>32,156</td>
                                <td>€745,238.90</td>
                                <td><span class="badge badge-success">97.9%</span></td>
                                <td><button class="btn btn-sm round btn-outline-info">Détails</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection