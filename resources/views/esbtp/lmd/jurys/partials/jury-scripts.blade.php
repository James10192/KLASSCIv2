<script>
function jurySalle(juryId) {
    return {
        tab: 'composition',
        membres: @json($membresData),
        decisions: @json($decisionsData),
        stats: @json($stats),
        quorum: @json($quorum),
        readiness: @json($readiness),
        newMembreUserId: '',
        newMembreRole: 'assesseur',
        busy: false,
        overrideOpen: false,
        reviewOpen: false,
        review: { kind: '', title: '', message: '', actionLabel: '', member: null },
        signatureOpen: false,
        signatureMember: null,
        signatureDrawing: false,
        signatureDrawn: false,
        reconcileEndpoint: '{{ route('esbtp.lmd.jurys.pv.reconcilier', $jury) }}',
        reconciliationState: 'idle',
        reconciliationMessage: '',
        officialDocument: @json($officialDocument ? ['reference' => $officialDocument->reference, 'version' => (int) $officialDocument->version, 'status' => $officialDocument->status, 'checksum_sha256' => $officialDocument->checksum_sha256, 'issued_at' => $officialDocument->issued_at?->toIso8601String(), 'pv_numero' => $officialDocument->pv_numero, 'actions' => ['canDownload' => true, 'canPreview' => true]] : null),
        juryPvPathExists: @json((bool) $jury->pv_path),
        form: { etudiantId: null, etudiant_name: '', decision_auto: '', decision: '', motif: '', vote_resultat: '' },

        init() {
            window.addEventListener('jury:decision-updated', (ev) => {
                this.applyDecisionUpdate(ev.detail);
            });
        },

        async request(method, url, body = null) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': body === null ? null : 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: body === null ? null : JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || ('Erreur ' + res.status));
                }
                return data;
            } finally {
                this.busy = false;
            }
        },

        post(url, body = {}) {
            return this.request('POST', url, body);
        },

        patch(url, body) {
            return this.request('PATCH', url, body);
        },

        delete(url) {
            return this.request('DELETE', url, null);
        },

        toast(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
        },

        async addMembre() {
            if (!this.newMembreUserId) {
                return;
            }
            try {
                const data = await this.post('{{ route('esbtp.lmd.jurys.membres.store', $jury) }}', {
                    user_id: parseInt(this.newMembreUserId),
                    role: this.newMembreRole,
                    present: true,
                });
                const existing = this.membres.findIndex(m => m.user_id === data.membre.user_id);
                if (existing >= 0) {
                    this.membres[existing] = data.membre;
                } else {
                    this.membres.push(data.membre);
                }
                this.quorum = data.quorum;
                this.readiness = data.readiness;
                this.newMembreUserId = '';
                this.toast('success', 'Membre ajouté.');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        requestRemoveMembre(m) {
            this.review = {
                kind: 'remove',
                title: 'Retirer ce membre',
                message: `Retirer ${m.user_name} de la composition du jury ? Le quorum sera recalculé.`,
                actionLabel: 'Retirer',
                member: m
            };
            this.reviewOpen = true;
        },

        async removeMembre(m) {
            try {
                const data = await this.delete(`/esbtp/lmd/jurys/${juryId}/membres/${m.id}`);
                this.membres = this.membres.filter(x => x.id !== m.id);
                this.quorum = data.quorum;
                this.readiness = data.readiness;
                this.toast('success', 'Membre retiré.');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        async appliquerAuto() {
            try {
                const data = await this.post('{{ route('esbtp.lmd.jurys.decisions.auto', $jury) }}');
                this.stats = data.stats;
                this.readiness = data.readiness;
                this.toast('success', `${data.created_count} décisions créées.`);
                setTimeout(() => window.location.reload(), 600);
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        openOverride(d) {
            @if(!$jury->isLocked())
            this.form = {
                etudiantId: d.etudiant_id,
                etudiant_name: d.etudiant_name,
                decision_auto: d.decision_auto,
                decision: d.decision,
                motif: d.motif_override || '',
                vote_resultat: d.vote_resultat || '',
            };
            this.overrideOpen = true;
            @endif
        },

        closeOverride() {
            this.overrideOpen = false;
        },

        async saveOverride() {
            if (!this.form.motif || this.form.motif.length < 5) {
                this.toast('error', 'Motif obligatoire (min 5 caractères).');
                return;
            }
            try {
                const data = await this.patch(`/esbtp/lmd/jurys/${juryId}/decisions/${this.form.etudiantId}`, {
                    decision: this.form.decision,
                    motif: this.form.motif,
                    vote_resultat: this.form.vote_resultat || null,
                });
                window.dispatchEvent(new CustomEvent('jury:decision-updated', { detail: data.decision }));
                this.stats = data.stats;
                this.readiness = data.readiness;
                this.overrideOpen = false;
                this.toast('success', 'Décision enregistrée.');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        applyDecisionUpdate(updated) {
            const idx = this.decisions.findIndex(d => d.etudiant_id === updated.etudiant_id);
            if (idx >= 0) {
                this.decisions[idx] = { ...this.decisions[idx], ...updated, etudiant_name: this.decisions[idx].etudiant_name };
            }
        },

        requestPv() {
            this.review = {
                kind: 'pv',
                title: 'Générer le procès-verbal',
                message: 'Les décisions seront verrouillées. Vérifiez la cohorte, les décisions et toutes les signatures avant de continuer.',
                actionLabel: 'Générer et verrouiller',
                member: null
            };
            this.reviewOpen = true;
        },

        async genererPv() {
            try {
                const data = await this.post('{{ route('esbtp.lmd.jurys.pv.generer', $jury) }}');
                this.toast('success', `PV ${data.pv.numero} généré.`);
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        requestPublication() {
            this.review = {
                kind: 'publish',
                title: 'Publier les décisions',
                message: 'Les décisions officielles seront projetées vers les bulletins et visibles par les étudiants.',
                actionLabel: 'Publier officiellement',
                member: null
            };
            this.reviewOpen = true;
        },

        async publier() {
            try {
                await this.post('{{ route('esbtp.lmd.jurys.publier', $jury) }}');
                this.toast('success', 'Jury publié.');
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        closeReview() {
            this.reviewOpen = false;
        },

        async confirmReview() {
            const review = this.review;
            this.reviewOpen = false;
            if (review.kind === 'remove') {
                await this.removeMembre(review.member);
            }
            if (review.kind === 'pv') {
                await this.genererPv();
            }
            if (review.kind === 'publish') {
                await this.publier();
            }
        },

        openSignature(member) {
            this.signatureMember = member;
            this.signatureOpen = true;
            this.signatureDrawn = false;
            this.$nextTick(() => this.clearSignature());
        },

        closeSignature() {
            this.signatureOpen = false;
            this.signatureMember = null;
            this.signatureDrawing = false;
        },

        signaturePoint(event) {
            const canvas = this.$refs.signatureCanvas;
            const rect = canvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * canvas.width / rect.width,
                y: (event.clientY - rect.top) * canvas.height / rect.height,
            };
        },

        startSignature(event) {
            const canvas = this.$refs.signatureCanvas;
            const point = this.signaturePoint(event);
            const context = canvas.getContext('2d');
            context.beginPath();
            context.moveTo(point.x, point.y);
            this.signatureDrawing = true;
        },

        drawSignature(event) {
            if (!this.signatureDrawing) {
                return;
            }
            const canvas = this.$refs.signatureCanvas;
            const point = this.signaturePoint(event);
            const context = canvas.getContext('2d');
            context.lineWidth = 2;
            context.lineCap = 'round';
            context.strokeStyle = '#0f172a';
            context.lineTo(point.x, point.y);
            context.stroke();
            this.signatureDrawn = true;
        },

        stopSignature() {
            this.signatureDrawing = false;
        },

        clearSignature() {
            const canvas = this.$refs.signatureCanvas;
            if (!canvas) {
                return;
            }
            const rect = canvas.getBoundingClientRect();
            canvas.width = Math.max(600, Math.round(rect.width * window.devicePixelRatio));
            canvas.height = Math.max(240, Math.round(rect.height * window.devicePixelRatio));
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            this.signatureDrawn = false;
        },

        async saveSignature() {
            if (!this.signatureMember || !this.signatureDrawn) {
                return;
            }
            try {
                const data = await this.post(`/esbtp/lmd/jurys/${juryId}/membres/${this.signatureMember.id}/signer`, {
                    signature_data: this.$refs.signatureCanvas.toDataURL('image/png'),
                });
                const member = this.membres.find(item => item.id === this.signatureMember.id);
                if (member) {
                    member.has_signed = data.membre.has_signed;
                    member.can_sign = data.membre.can_sign;
                }
                this.readiness = data.readiness;
                this.closeSignature();
                this.toast('success', 'Signature enregistrée avec preuve d\'authentification.');
            } catch (e) {
                this.toast('error', e.message);
            }
        },
        async reconcilePv() {
            this.reconciliationState = 'loading';
            this.reconciliationMessage = 'Réconciliation en cours...';
            try {
                const data = await this.post(this.reconcileEndpoint);
                const document = data?.document ?? null;
                const actions = {
                    canDownload: true,
                    canPreview: true,
                    ...(this.officialDocument?.actions || {}),
                    ...(document?.actions || {}),
                };
                if (!document) {
                    throw new Error(data?.message || 'Réponse de réconciliation invalide.');
                }
                this.officialDocument = {

                    reference: document.reference ?? this.officialDocument?.reference ?? null,
                    version: document.version ?? this.officialDocument?.version ?? null,
                    status: document.status ?? this.officialDocument?.status ?? null,
                    hash: document.hash ?? document.checksum_sha256 ?? this.officialDocument?.hash ?? this.officialDocument?.checksum_sha256 ?? null,
                    checksum_sha256: document.hash ?? document.checksum_sha256 ?? this.officialDocument?.hash ?? this.officialDocument?.checksum_sha256 ?? null,
                    issued_at: document.issued_at ?? this.officialDocument?.issued_at ?? null,
                    pv_numero: this.officialDocument?.pv_numero ?? '{{ $jury->pv_numero }}',
                    actions,
                };
                this.juryPvPathExists = true;
                this.reconciliationState = 'success';
                this.reconciliationMessage = document.message || data?.message || 'Réconciliation réalisée avec succès.';
                this.toast('success', this.reconciliationMessage);
            } catch (e) {
                this.reconciliationState = 'error';
                this.reconciliationMessage = e.message || 'Échec de la réconciliation. Réessayez.';
                this.toast('error', this.reconciliationMessage);
            }
        },
    };
}
</script>






