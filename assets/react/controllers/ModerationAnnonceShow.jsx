import React, { useEffect, useState } from 'react';
import ReactMarkdown from 'react-markdown';

export default function ModerationAnnonceShow({ id }) {
    const [annonce, setAnnonce] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [processing, setProcessing] = useState(false);

    const [showRejectModal, setShowRejectModal] = useState(false);
    const [refusalReason, setRefusalReason] = useState('');
    const [reasonError, setReasonError] = useState('');

    useEffect(() => {
        loadAnnonce();
    }, [id]);

    const loadAnnonce = async () => {
        setLoading(true);
        try {
            const response = await fetch(`/api/admin/annonce/${id}`);
            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || "Impossible de charger l'annonce");
            }
            const data = await response.json();
            setAnnonce(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const handleValidate = async () => {
        if (!annonce) return;
        setProcessing(true);
        try {
            const response = await fetch(`/api/admin/annonce/${annonce.id}/decide`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'validate' }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || 'Erreur lors de la validation');
            }

            window.location.href = '/admin/dashboard';
        } catch (err) {
            setError(err.message);
        } finally {
            setProcessing(false);
        }
    };

    const openRejectModal = () => {
        setRefusalReason('');
        setReasonError('');
        setShowRejectModal(true);
    };

    const closeRejectModal = () => {
        setShowRejectModal(false);
        setRefusalReason('');
        setReasonError('');
    };

    const handleRejectConfirm = async () => {
        if (!refusalReason.trim()) {
            setReasonError('Le motif du refus est obligatoire');
            return;
        }

        if (refusalReason.trim().length < 10) {
            setReasonError('Le motif doit contenir au moins 10 caractères');
            return;
        }

        setProcessing(true);
        try {
            const response = await fetch(`/api/admin/annonce/${annonce.id}/decide`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reject', reason: refusalReason.trim() }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || 'Erreur lors du refus');
            }

            window.location.href = '/admin/dashboard';
        } catch (err) {
            setError(err.message);
        } finally {
            setProcessing(false);
        }
    };

    if (loading) {
        return (
            <div className="container py-5">
                <div className="text-center">
                    <div className="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="container py-5">
                <div className="alert alert-danger" role="alert">
                    <i className="bi bi-exclamation-triangle me-2"></i>
                    {error}
                </div>
            </div>
        );
    }

    if (!annonce) return null;

    return (
        <div className="container py-4">
            <div className="row g-4">
                <div className="col-lg-7">
                    <div className="card shadow-sm border-0">
                        <div className="card-body">
                            <h2 className="h4 mb-3">{annonce.title}</h2>
                            <div className="mb-3 text-muted small">
                                <i className="bi bi-calendar me-1"></i>
                                {annonce.createdAt}
                                <span className="mx-2">•</span>
                                <i className="bi bi-geo-alt me-1"></i>
                                {annonce.campus}
                            </div>

                            <div className="mb-3">
                                <span className="badge bg-secondary me-2">{annonce.price}</span>
                                {annonce.category && (
                                    <span className="badge bg-light text-dark">{annonce.category}</span>
                                )}
                            </div>

                            <div className="mb-4 markdown-content">
                                <ReactMarkdown>{annonce.description}</ReactMarkdown>
                            </div>

                            {annonce.images?.length > 0 && (
                                <div className="row g-2">
                                    {annonce.images.map((src, idx) => (
                                        <div className="col-6" key={idx}>
                                            <img
                                                src={src}
                                                alt={`image-${idx}`}
                                                className="img-fluid rounded border"
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="col-lg-5">
                    <div className="card shadow-sm border-0">
                        <div className="card-body">
                            <h5 className="mb-3">Auteur</h5>
                            <div className="mb-2">
                                <i className="bi bi-person me-2"></i>
                                {annonce.owner?.cas_uid}
                            </div>
                            <div className="mb-4">
                                <i className="bi bi-envelope me-2"></i>
                                {annonce.owner?.email}
                            </div>

                            <div className="d-grid gap-2">
                                <button
                                    className="btn btn-success"
                                    onClick={handleValidate}
                                    disabled={processing}
                                >
                                    <i className="bi bi-check-circle me-2"></i>
                                    Valider l'annonce
                                </button>
                                <button
                                    className="btn btn-danger"
                                    onClick={openRejectModal}
                                    disabled={processing}
                                >
                                    <i className="bi bi-x-circle me-2"></i>
                                    Refuser l'annonce
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="alert alert-warning mt-3" role="alert">
                        <i className="bi bi-info-circle me-2"></i>
                        Cette annonce est en attente de validation. Vérifie le contenu, les images et la conformité à la charte.
                    </div>
                </div>
            </div>

            {showRejectModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} tabIndex="-1">
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header bg-danger text-white">
                                <h5 className="modal-title">
                                    <i className="bi bi-x-circle me-2"></i>
                                    Refuser l'annonce
                                </h5>
                                <button type="button" className="btn-close btn-close-white" onClick={closeRejectModal}></button>
                            </div>
                            <div className="modal-body">
                                <p className="mb-3">
                                    Veuillez indiquer le motif du refus. Ce message sera visible par l'auteur.
                                </p>
                                <div className="mb-3">
                                    <label className="form-label">
                                        Motif du refus <span className="text-danger">*</span>
                                    </label>
                                    <textarea
                                        className={`form-control ${reasonError ? 'is-invalid' : ''}`}
                                        rows="5"
                                        value={refusalReason}
                                        onChange={(e) => {
                                            setRefusalReason(e.target.value);
                                            setReasonError('');
                                        }}
                                        placeholder="Exemple : Photo non conforme, description insuffisante, contenu inapproprié..."
                                        maxLength="500"
                                    ></textarea>
                                    {reasonError && <div className="invalid-feedback">{reasonError}</div>}
                                    <div className="form-text">
                                        {refusalReason.length}/500 caractères (minimum 10)
                                    </div>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-secondary" onClick={closeRejectModal} disabled={processing}>
                                    Annuler
                                </button>
                                <button type="button" className="btn btn-danger" onClick={handleRejectConfirm} disabled={processing}>
                                    {processing ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2"></span>
                                            Refus en cours...
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-x-circle me-2"></i>
                                            Confirmer le refus
                                        </>
                                    )}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
