<section class="tab-pane fade p-4 bg-white rounded shadow-sm" id="audit">
  <h2 class="h5 mb-3">Historique d'audit admin</h2>
  <div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Utilisateur</th><th>Rôle</th><th>Action</th><th>Message</th></tr></thead><tbody>
  @forelse($audit_logs as $l)
    <tr><td>{{ $l['created_at'] }}</td><td>{{ $l['user_code'] }}</td><td>{{ $l['role'] }}</td><td>{{ $l['action'] }}</td><td>{{ $l['message'] }}</td></tr>
  @empty
    <tr><td colspan="5" class="text-center text-muted">Aucun log</td></tr>
  @endforelse
  </tbody></table></div>
</section>
