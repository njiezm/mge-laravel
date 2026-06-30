@php
  $pdo = $pdo ?? null;
@endphp

<div class="tab-pane fade" id="view-repartition" role="tabpanel" aria-labelledby="repartition-tab" tabindex="0">
  <div class="table-responsive mt-3">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Collaborateur</th>
          <th>Janvier</th>
          <th>Février</th>
          <th>Mars</th>
          <th>Avril</th>
          <th>Mai</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @if($pdo)
          @php
            $stmt = $pdo->query("
              SELECT
                u.id AS collaborateur_id,
                COALESCE(
                  NULLIF(CONCAT_WS(' ', NULLIF(u.prenom, ''), NULLIF(u.nom, '')), ''),
                  u.code_utilisateur
                ) AS collaborateur,
                SUM(v.janvier) AS janvier,
                SUM(v.fevrier) AS fevrier,
                SUM(v.mars) AS mars,
                SUM(v.avril) AS avril,
                SUM(v.mai) AS mai,
                SUM(v.janvier + v.fevrier + v.mars + v.avril + v.mai) AS total
              FROM ventilation v
              JOIN utilisateurs u ON u.id = v.collaborateur_id
              WHERE u.role = 'collaborateur'
              GROUP BY u.id, collaborateur
              ORDER BY collaborateur
            ");
          @endphp
          @while($row = $stmt->fetch(PDO::FETCH_ASSOC))
            <tr>
              <td>{{ $row['collaborateur'] }}</td>
              <td>{{ number_format($row['janvier'], 2) }} h</td>
              <td>{{ number_format($row['fevrier'], 2) }} h</td>
              <td>{{ number_format($row['mars'], 2) }} h</td>
              <td>{{ number_format($row['avril'], 2) }} h</td>
              <td>{{ number_format($row['mai'], 2) }} h</td>
              <td><strong>{{ number_format($row['total'], 2) }} h</strong></td>
            </tr>
          @endwhile
        @endif
      </tbody>
    </table>
  </div>
</div>
