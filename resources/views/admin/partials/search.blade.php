<section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="search">
  <h2 class="h5 mb-3">Recherche globale</h2>
  <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-2 mb-3">
    <div class="col-md-8"><input name="q" value="{{ $search_q }}" class="form-control" placeholder="Nom, code dossier, email, action..." /></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Rechercher</button></div>
  </form>
</section>
