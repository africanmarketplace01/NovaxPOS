@extends('backend.layouts.app')

@section('title', 'API Tokens')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="mb-0 fw-bold">API Tokens</h5>
            <small class="text-muted">Personal access tokens for integrating with the NovaxPOS REST API.</small>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTokenModal">
            <i class="fas fa-plus me-1"></i>Create Token
        </button>
    </div>

    {{-- One-time token display --}}
    @if(session('new_token'))
    <div class="alert alert-success border-0 mb-4" style="background:#f0fdf4;border-left:4px solid #10b981 !important;">
        <div class="fw-semibold mb-1"><i class="fas fa-key me-1"></i>Your new API token — copy it now!</div>
        <div class="font-monospace p-2 rounded mb-2" style="background:#fff;border:1px solid #bbf7d0;word-break:break-all;font-size:.85rem;">
            {{ session('new_token') }}
        </div>
        <button class="btn btn-sm btn-outline-success" onclick="copyToken()">
            <i class="fas fa-copy me-1"></i>Copy
        </button>
        <div class="mt-2 text-muted" style="font-size:.8rem;">
            <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
            This token will not be shown again. Store it somewhere safe.
        </div>
    </div>
    @endif

    @if(session('message'))
    <div class="alert alert-success py-2">{{ session('message') }}</div>
    @endif

    {{-- Token list --}}
    <div class="card">
        <div class="card-header fw-semibold">Active Tokens</div>
        @if($tokens->isEmpty())
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-key fa-2x mb-3 d-block"></i>
            No API tokens yet. Create one to get started.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.875rem;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-3">Name</th>
                        <th>Abilities</th>
                        <th>Last Used</th>
                        <th>Expires</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tokens as $token)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $token->name }}</td>
                        <td>
                            @foreach($token->abilities ?? ['*'] as $ab)
                                <span class="badge" style="background:rgba(0,102,204,.1);color:#0066CC;font-size:.72rem;">{{ $ab }}</span>
                            @endforeach
                        </td>
                        <td class="text-muted">{{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}</td>
                        <td>
                            @if($token->expires_at)
                                @if($token->isExpired())
                                    <span class="badge bg-danger">Expired</span>
                                @else
                                    <span class="text-muted">{{ $token->expires_at->format('M j, Y') }}</span>
                                @endif
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $token->created_at->format('M j, Y') }}</td>
                        <td class="text-end pe-3">
                            <form method="POST" action="{{ route('api-tokens.destroy', $token) }}"
                                  onsubmit="return confirm('Revoke this token?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash-alt"></i> Revoke
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- API docs teaser --}}
    <div class="card mt-4">
        <div class="card-header fw-semibold d-flex align-items-center gap-2">
            <i class="fas fa-code" style="color:#0066CC"></i>
            Quick Start — REST API
        </div>
        <div class="card-body">
            <p class="text-muted mb-2" style="font-size:.85rem;">
                Send your token as a Bearer header on every API request:
            </p>
            <pre class="p-3 rounded" style="background:#1e293b;color:#e2e8f0;font-size:.8rem;overflow-x:auto;"><code>curl -H "Authorization: Bearer {YOUR_TOKEN}" \
     {{ url('/api/v1/products') }}</code></pre>
            <div class="row g-2 mt-2">
                @foreach([
                    ['GET', '/api/v1/products',  'List all products'],
                    ['GET', '/api/v1/sales',     'List recent sales'],
                    ['GET', '/api/v1/customers', 'List customers'],
                ] as [$method, $path, $desc])
                <div class="col-md-4">
                    <div class="p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:.78rem;">
                        <span class="badge me-1" style="background:#10b981;color:#fff;">{{ $method }}</span>
                        <code>{{ $path }}</code>
                        <div class="text-muted mt-1">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- Create Token Modal --}}
<div class="modal fade" id="createTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold">Create API Token</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('api-tokens.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Token Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g. Mobile App, Webhook Integration">
                        <div class="form-text">A label to identify this token.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Abilities</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="*" id="ab_all" checked>
                                <label class="form-check-label" for="ab_all">Full Access (*)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="read" id="ab_read">
                                <label class="form-check-label" for="ab_read">Read Only</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="write" id="ab_write">
                                <label class="form-check-label" for="ab_write">Write</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Expiry</label>
                        <select name="expires_in" class="form-select">
                            <option value="">Never</option>
                            <option value="30">30 days</option>
                            <option value="90">90 days</option>
                            <option value="180">6 months</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create Token</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToken() {
    const txt = document.querySelector('.font-monospace').textContent.trim();
    navigator.clipboard.writeText(txt).then(() => {
        const btn = document.querySelector('[onclick="copyToken()"]');
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy me-1"></i>Copy'; }, 2500);
    });
}
</script>
@endpush

@endsection
