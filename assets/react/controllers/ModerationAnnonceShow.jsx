import React, { useEffect, useState } from 'react';
import ReactMarkdown from 'react-markdown';

export default function ModerationAnnonceShow({ id }) {
    const [annonce, setAnnonce] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [processing, setProcessing] = useState(false);
    
    // État du verrouillage
    const [lockStatus, setLockStatus] = useState(null);
    const [lockError, setLockError] = useState('');
    const [isLocked, setIsLocked] = useState(false);

    const [showRejectModal, setShowRejectModal] = useState(false);
    const [refusalReason, setRefusalReason] = useState('');
    const [reasonError, setReasonError] = useState('');
    
    // État pour le carrousel d'images
    const [currentImageIndex, setCurrentImageIndex] = useState(0);

    useEffect(() => {
        loadAnnonce();
        lockAnnonce();
        
        // Déverrouiller quand on quitte la page
        return () => {
            unlockAnnonce();
        };
    }, [id]);

    const lockAnnonce = async () => {
        try {
            const response = await fetch(`/api/moderation/annonce/${id}/lock`, {
                method: 'POST',
            });
            const data = await response.json();
            
            if (!response.ok) {
                // Code 423 = annonce verrouillée par quelqu'un d'autre
                if (response.status === 423) {
                    setLockError(data.message || 'Cette annonce est en cours de traitement par un autre modérateur.');
                    setLockStatus(data);
                    setIsLocked(false);
                } else {
                    throw new Error(data.error || 'Erreur lors du verrouillage');
                }
            } else {
                setIsLocked(true);
                setLockStatus(data);
            }
        } catch (err) {
            console.error('Erreur de verrouillage:', err);
        }
    };

    const unlockAnnonce = async () => {
        if (!isLocked) return;
        
        try {
            await fetch(`/api/moderation/annonce/${id}/unlock`, {
                method: 'POST',
            });
        } catch (err) {
            console.error('Erreur de déverrouillage:', err);
        }
    };

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
        if (!annonce || !isLocked) return;
        setProcessing(true);
        try {
            const response = await fetch(`/api/admin/annonce/${annonce.id}/decide`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'validate' }),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                
                // Si l'annonce est verrouillée par un autre
                if (response.status === 423) {
                    setLockError(data.message || 'Cette annonce est en cours de traitement par un autre modérateur.');
                    setIsLocked(false);
                    return;
                }
                
                throw new Error(data.message || data.error || 'Erreur lors de la validation');
            }

            // Redirection après succès (le verrou est automatiquement libéré côté backend)
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

        if (!isLocked) {
            setLockError('Vous ne pouvez pas refuser cette annonce car elle n\'est pas verrouillée par vous.');
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
                
                // Si l'annonce est verrouillée par un autre
                if (response.status === 423) {
                    setLockError(data.message || 'Cette annonce est en cours de traitement par un autre modérateur.');
                    setIsLocked(false);
                    closeRejectModal();
                    return;
                }
                
                throw new Error(data.message || data.error || 'Erreur lors du refus');
            }

            // Redirection après succès (le verrou est automatiquement libéré côté backend)
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
                                {annonce.campuses && annonce.campuses.length > 0 
                                    ? annonce.campuses.map(c => ({ CALAIS: 'Calais', DUNKERQUE: 'Dunkerque', BOULOGNE: 'Boulogne', SAINT_OMER: 'Saint-Omer' }[c] || c)).join(', ') 
                                    : 'Non spécifié'}
                            </div>

                            <div className="mb-3">
                                <span className="badge bg-secondary me-2">{annonce.price}</span>
                                {(annonce.customCategoryName || annonce.category) && (
                                    <span className="badge bg-light text-dark">{annonce.customCategoryName || annonce.category}</span>
                                )}
                            </div>

                            <div className="mb-4 markdown-content">
                                <ReactMarkdown>{annonce.description}</ReactMarkdown>
                            </div>

                            {annonce.images?.length > 0 && (
                                <div>
                                    <h5 className="mb-3">Images</h5>
                                    
                                    {/* Carrousel principal */}
                                    <div className="position-relative mb-3" style={{
                                        backgroundColor: '#f0f0f0',
                                        borderRadius: '8px',
                                        overflow: 'hidden',
                                        minHeight: '350px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center'
                                    }}>
                                        <img
                                            src={annonce.images[currentImageIndex]}
                                            alt={`Image ${currentImageIndex + 1}`}
                                            className="img-fluid"
                                            style={{ maxHeight: '400px', objectFit: 'contain' }}
                                        />
                                        
                                        {/* Boutons navigation */}
                                        {annonce.images.length > 1 && (
                                            <>
                                                <button
                                                    type="button"
                                                    className="btn btn-light position-absolute start-0 top-50 translate-middle-y ms-2"
                                                    onClick={() => setCurrentImageIndex((currentImageIndex - 1 + annonce.images.length) % annonce.images.length)}
                                                    style={{ width: '40px', height: '40px' }}
                                                >
                                                    <i className="bi bi-chevron-left"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    className="btn btn-light position-absolute end-0 top-50 translate-middle-y me-2"
                                                    onClick={() => setCurrentImageIndex((currentImageIndex + 1) % annonce.images.length)}
                                                    style={{ width: '40px', height: '40px' }}
                                                >
                                                    <i className="bi bi-chevron-right"></i>
                                                </button>
                                            </>
                                        )}
                                        
                                        {/* Badge image count */}
                                        <div className="position-absolute bottom-0 start-50 translate-middle-x mb-2">
                                            <span className="badge bg-dark">{currentImageIndex + 1} / {annonce.images.length}</span>
                                        </div>
                                    </div>
                                    
                                    {/* Miniatures */}
                                    {annonce.images.length > 1 && (
                                        <div className="d-flex gap-2 overflow-auto pb-2">
                                            {annonce.images.map((src, index) => (
                                                <div key={index} className="position-relative flex-shrink-0">
                                                    <img
                                                        src={src}
                                                        alt={`Miniature ${index + 1}`}
                                                        className="rounded border-2 cursor-pointer"
                                                        style={{
                                                            width: '80px',
                                                            height: '80px',
                                                            objectFit: 'cover',
                                                            borderColor: currentImageIndex === index ? '#0d6efd' : '#ccc',
                                                            borderWidth: '2px',
                                                            cursor: 'pointer',
                                                            opacity: currentImageIndex === index ? 1 : 0.7
                                                        }}
                                                        onClick={() => setCurrentImageIndex(index)}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="col-lg-5">
                    {/* Affichage de l'état du verrouillage */}
                    {lockError && (
                        <div className="alert alert-danger" role="alert">
                            <i className="bi bi-lock-fill me-2"></i>
                            <strong>Annonce verrouillée</strong>
                            <p className="mb-0 mt-2">{lockError}</p>
                            {lockStatus?.locked_by && (
                                <p className="mb-0 mt-1 small">
                                    Verrouillée par <strong>{lockStatus.locked_by}</strong> le {lockStatus.locked_at}
                                </p>
                            )}
                            <hr />
                            <a href="/admin/dashboard" className="btn btn-sm btn-secondary">
                                <i className="bi bi-arrow-left me-1"></i>
                                Retour à la liste
                            </a>
                        </div>
                    )}
                    
                    {isLocked && (
                        <div className="alert alert-success" role="alert">
                            <i className="bi bi-unlock-fill me-2"></i>
                            Vous avez verrouillé cette annonce. Vous pouvez maintenant la modérer.
                        </div>
                    )}
                    
                    <div className="card shadow-sm border-0">
                        <div className="card-body">
                            <h5 className="mb-3">Auteur</h5>
                            <div className="mb-2">
                                <i className="bi bi-person me-2"></i>
                                {annonce.owner?.cas_uid}
                            </div>
                            <div className="mb-4 text-muted small">
                                Adresse e-mail masquée (donnée sensible)
                            </div>

                            <div className="d-grid gap-2">
                                <button
                                    className="btn btn-success"
                                    onClick={handleValidate}
                                    disabled={processing || !isLocked}
                                >
                                    <i className="bi bi-check-circle me-2"></i>
                                    Valider l'annonce
                                </button>
                                <button
                                    className="btn btn-danger"
                                    onClick={openRejectModal}
                                    disabled={processing || !isLocked}
                                >
                                    <i className="bi bi-x-circle me-2"></i>
                                    Refuser l'annonce
                                </button>
                            </div>
                        </div>
                    </div>

                    {isLocked && (
                        <div className="alert alert-warning mt-3" role="alert">
                            <i className="bi bi-info-circle me-2"></i>
                            Cette annonce est en attente de validation. Vérifie le contenu, les images et la conformité à la charte.
                        </div>
                    )}
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
