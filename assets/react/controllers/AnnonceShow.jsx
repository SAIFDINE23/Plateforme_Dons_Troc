import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
    MapPin, Tag, Calendar, User, Edit, Trash2, 
    CheckCircle, MessageCircle, ArrowLeft, Gift, Repeat,
    ShieldCheck, AlertCircle
} from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import toast, { Toaster } from 'react-hot-toast';

export default function AnnonceShow({ id }) {
    const [annonce, setAnnonce] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [deleting, setDeleting] = useState(false);
    const [finishing, setFinishing] = useState(false);
    const [isFavorite, setIsFavorite] = useState(false);
    const [currentImageIndex, setCurrentImageIndex] = useState(0);

    // Charger les détails de l'annonce
    useEffect(() => {
        console.log('AnnonceShow: Loading annonce', id);
        fetch(`/api/annonces/${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('AnnonceShow: Data loaded', data);
                console.log('AnnonceShow: isOwner =', data.isOwner);
                console.log('AnnonceShow: Owner =', data.owner);
                setAnnonce(data);
                setIsFavorite(!!data.isFavorite);
                setCurrentImageIndex(0);
                setLoading(false);
            })
            .catch(err => {
                console.error('AnnonceShow: Error loading', err);
                setError(err.message);
                setLoading(false);
            });
    }, [id]);

    // Marquer comme terminé
    const handleFinish = async () => {
        if (!window.confirm('Êtes-vous sûr ? Cela marquera votre annonce comme donnée/troquée.')) {
            return;
        }

        setFinishing(true);
        try {
            const response = await fetch(`/api/annonces/${id}/finish`, {
                method: 'PATCH',
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour');
            }

            const data = await response.json();
            setAnnonce(prev => ({ ...prev, state: 'COMPLETED' }));
            alert('✅ Annonce marquée comme terminée');
        } catch (err) {
            alert('❌ Erreur: ' + err.message);
        } finally {
            setFinishing(false);
        }
    };

    // Supprimer l'annonce
    const handleDelete = async () => {
        if (!window.confirm('⚠️ Êtes-vous sûr ? Cette action est irréversible.')) {
            return;
        }

        setDeleting(true);
        try {
            const response = await fetch(`/api/annonces/${id}`, {
                method: 'DELETE',
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la suppression');
            }

            alert('✅ Annonce supprimée');
            window.location.href = '/mes-annonces';
        } catch (err) {
            alert('❌ Erreur: ' + err.message);
            setDeleting(false);
        }
    };

    const handleContact = async () => {
        try {
            const response = await fetch(`/api/annonces/${id}/conversation`, {
                method: 'POST',
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || 'Erreur lors de la création de la conversation');
            }

            const data = await response.json();
            const conversationId = data.conversationId;
            if (conversationId) {
                window.location.href = `/mes-messages/${conversationId}`;
            } else {
                window.location.href = '/mes-messages';
            }
        } catch (err) {
            alert('❌ Erreur: ' + err.message);
        }
    };

    const handleToggleFavorite = async () => {
        const nextValue = !isFavorite;
        setIsFavorite(nextValue);

        try {
            const response = await fetch(`/api/annonces/${id}/favorite`, {
                method: 'POST',
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour');
            }

            const data = await response.json();
            setIsFavorite(!!data.isFavorite);

            if (data.isFavorite) {
                toast.success('Ajouté aux favoris');
            } else {
                toast('Retiré des favoris');
            }
        } catch {
            setIsFavorite(!nextValue);
            toast.error('Erreur lors de la mise à jour');
        }
    };

    // État de chargement
    if (loading) {
        return (
            <motion.div 
                className="text-center py-5"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
            >
                <div className="spinner-eilco mx-auto mb-3"></div>
                <p className="text-muted">Chargement de l'annonce...</p>
            </motion.div>
        );
    }

    // Erreur
    if (error || !annonce) {
        return (
            <motion.div 
                className="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3" 
                role="alert"
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
            >
                <AlertCircle size={24} />
                <span>{error || 'Annonce introuvable'}</span>
            </motion.div>
        );
    }

    // Récupérer les infos de l'annonce
    const {
        title,
        description,
        price,
        campus,
        categoryName,
        image,
        images,
        owner,
        isOwner,
        state,
        createdAt,
    } = annonce;

    const isCompleted = state === 'COMPLETED';
    const getStatusBadge = (currentState) => {
        const badges = {
            PUBLISHED: { label: 'Publiée', class: 'status-published' },
            PENDING: { label: 'En attente', class: 'status-pending' },
            PENDING_REVIEW: { label: 'En attente de validation', class: 'status-pending' },
            REJECTED: { label: 'Rejetée', class: 'status-rejected' },
            COMPLETED: { label: 'Terminée', class: 'status-completed' },
            DRAFT: { label: 'Brouillon', class: 'status-draft' },
            ARCHIVED: { label: 'Archivée', class: 'status-archived' }
        };

        return badges[currentState] || { label: currentState || 'Inconnu', class: 'status-pending' };
    };

    const status = getStatusBadge(state);
    const campusLabels = {
        CALAIS: 'Calais',
        DUNKERQUE: 'Dunkerque',
        BOULOGNE: 'Boulogne',
        SAINT_OMER: 'Saint-Omer',
    };

    return (
        <>
            <Toaster position="top-right" />
            <motion.div 
                className="row g-4"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.5 }}
            >
            {/* COLONNE GAUCHE : IMAGE */}
            <div className="col-lg-6">
                <AnimatePresence>
                    {isCompleted && (
                        <motion.div 
                            className="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-center gap-2" 
                            role="alert"
                            initial={{ opacity: 0, y: -10 }}
                            animate={{ opacity: 1, y: 0 }}
                        >
                            <CheckCircle size={20} />
                            <strong>Cet objet a déjà trouvé preneur</strong>
                        </motion.div>
                    )}
                </AnimatePresence>

                <motion.div 
                    className="card card-annonce"
                    whileHover={{ y: -5 }}
                    transition={{ duration: 0.2 }}
                >
                    <div className="position-relative overflow-hidden">
                        <motion.img
                            src={(images && images.length > 0 ? images[currentImageIndex] : image) || 'https://via.placeholder.com/600x600/004E86/FFFFFF?text=Pas+d\'image'}
                            alt={title}
                            className="card-img-top"
                            style={{ height: '500px', objectFit: 'cover', cursor: 'pointer' }}
                            whileHover={{ scale: 1.05 }}
                            transition={{ duration: 0.3 }}
                        />

                        {images && images.length > 1 && (
                            <>
                                <button
                                    type="button"
                                    className="btn btn-light position-absolute start-0 top-50 translate-middle-y ms-3"
                                    onClick={() => setCurrentImageIndex((currentImageIndex - 1 + images.length) % images.length)}
                                    style={{ width: '40px', height: '40px', padding: 0, opacity: 0.85 }}
                                >
                                    <i className="bi bi-chevron-left"></i>
                                </button>
                                <button
                                    type="button"
                                    className="btn btn-light position-absolute end-0 top-50 translate-middle-y me-3"
                                    onClick={() => setCurrentImageIndex((currentImageIndex + 1) % images.length)}
                                    style={{ width: '40px', height: '40px', padding: 0, opacity: 0.85 }}
                                >
                                    <i className="bi bi-chevron-right"></i>
                                </button>
                                <div className="position-absolute bottom-0 start-50 translate-middle-x mb-3">
                                    <span className="badge bg-dark">{currentImageIndex + 1} / {images.length}</span>
                                </div>
                            </>
                        )}
                        
                        {/* Badge Type (Don/Troc) */}
                        <div className="position-absolute top-0 end-0 m-3">
                            <motion.span 
                                className={`badge ${price === 'Gratuit' ? 'bg-success' : 'badge-accent'} fs-6 shadow`}
                                initial={{ scale: 0 }}
                                animate={{ scale: 1 }}
                                transition={{ delay: 0.3 }}
                            >
                                {price === 'Gratuit' ? (
                                    <><Gift size={18} className="me-1" /> Don</>
                                ) : (
                                    <><Repeat size={18} className="me-1" /> Troc</>
                                )}
                            </motion.span>
                        </div>

                        <div className="position-absolute top-0 start-0 m-3">
                            <span className={`status-badge ${status.class}`}>
                                {status.label}
                            </span>
                        </div>
                    </div>
                    
                    <div className="card-body">
                        {createdAt && (() => {
                            const date = new Date(createdAt);
                            return !isNaN(date.getTime()) ? (
                                <div className="d-flex align-items-center text-muted small">
                                    <Calendar size={16} className="me-2" style={{ color: '#009FE3' }} />
                                    <span>Publié le {date.toLocaleDateString('fr-FR', {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric'
                                    })}</span>
                                </div>
                            ) : null;
                        })()}
                    </div>
                </motion.div>
            </div>

            {/* COLONNE DROITE : DÉTAILS & ACTIONS */}
            <div className="col-lg-6">
                {/* Titre */}
                <motion.div 
                    className="d-flex align-items-center justify-content-between gap-3 mb-4"
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: 0.2 }}
                >
                    <h1 className="mb-0">{title}</h1>
                    <button
                        type="button"
                        className={`btn rounded-circle shadow-sm d-flex align-items-center justify-content-center favorite-toggle-btn ${isFavorite ? 'is-favorite' : ''}`}
                        onClick={handleToggleFavorite}
                        aria-label={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                        title={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                    >
                        <i className={`bi ${isFavorite ? 'bi-heart-fill' : 'bi-heart'} favorite-toggle-icon`}></i>
                    </button>
                </motion.div>

                {/* Métadonnées - Campus & Catégorie */}
                <motion.div 
                    className="card border-0 bg-light mb-4"
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.3 }}
                >
                    <div className="card-body">
                        <div className="row">
                            <div className="col-6">
                                <div className="d-flex align-items-center gap-2 mb-2">
                                    <MapPin size={18} style={{ color: '#009FE3' }} />
                                    <h6 className="text-muted mb-0 small">Campus</h6>
                                </div>
                                <p className="fw-bold mb-0">
                                    {campusLabels[campus] || campus}
                                </p>
                            </div>
                            <div className="col-6">
                                <div className="d-flex align-items-center gap-2 mb-2">
                                    <Tag size={18} style={{ color: '#F07D00' }} />
                                    <h6 className="text-muted mb-0 small">Catégorie</h6>
                                </div>
                                <p className="fw-bold mb-0">{categoryName || 'Non catégorisé'}</p>
                            </div>
                        </div>
                    </div>
                </motion.div>

                {/* Description */}
                <motion.div 
                    className="mb-4"
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.4 }}
                >
                    <h6 className="text-muted mb-3">Description</h6>
                    <div className="card border-0 shadow-sm p-4">
                        <div className="markdown-content">
                            <ReactMarkdown>{description}</ReactMarkdown>
                        </div>
                    </div>
                </motion.div>

                {/* Vendeur - Visible uniquement si je suis le propriétaire */}
                {isOwner && (
                    <motion.div 
                        className="card border-0 shadow-sm mb-4"
                        style={{ background: 'linear-gradient(135deg, rgba(0, 78, 134, 0.05), rgba(0, 159, 227, 0.05))' }}
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.5 }}
                    >
                        <div className="card-body">
                            <div className="d-flex align-items-center gap-2 mb-3">
                                <ShieldCheck size={20} style={{ color: '#004E86' }} />
                                <h6 className="mb-0" style={{ color: '#004E86' }}>Mes informations</h6>
                            </div>
                            <div className="d-flex align-items-center gap-2 mb-2">
                                <User size={18} className="text-muted" />
                                <strong>{owner.username}</strong>
                            </div>
                        </div>
                    </motion.div>
                )}

                {/* ACTIONS - CAS 1 : PROPRIÉTAIRE */}
                {isOwner ? (
                    <motion.div 
                        className="d-grid gap-3"
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.6 }}
                    >
                        {!isCompleted && (
                            <>
                                <motion.a
                                    href={`/annonce/${id}/edit`}
                                    className="btn btn-warning btn-lg d-flex align-items-center justify-content-center gap-2"
                                    whileHover={{ scale: 1.02 }}
                                    whileTap={{ scale: 0.98 }}
                                >
                                    <Edit size={20} />
                                    <span>Modifier</span>
                                </motion.a>

                                <motion.button
                                    className="btn btn-success btn-lg d-flex align-items-center justify-content-center gap-2"
                                    onClick={handleFinish}
                                    disabled={finishing}
                                    whileHover={{ scale: finishing ? 1 : 1.02 }}
                                    whileTap={{ scale: finishing ? 1 : 0.98 }}
                                >
                                    {finishing ? (
                                        <>
                                            <div className="spinner-border spinner-border-sm"></div>
                                            <span>Traitement...</span>
                                        </>
                                    ) : (
                                        <>
                                            <CheckCircle size={20} />
                                            <span>J'ai donné/troqué cet objet</span>
                                        </>
                                    )}
                                </motion.button>
                            </>
                        )}

                        <motion.button
                            className="btn btn-danger btn-lg d-flex align-items-center justify-content-center gap-2"
                            onClick={handleDelete}
                            disabled={deleting}
                            whileHover={{ scale: deleting ? 1 : 1.02 }}
                            whileTap={{ scale: deleting ? 1 : 0.98 }}
                        >
                            {deleting ? (
                                <>
                                    <div className="spinner-border spinner-border-sm"></div>
                                    <span>Suppression...</span>
                                </>
                            ) : (
                                <>
                                    <Trash2 size={20} />
                                    <span>Supprimer définitivement</span>
                                </>
                            )}
                        </motion.button>

                        <a href="/mes-annonces" className="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">
                            <ArrowLeft size={18} />
                            <span>Retour à mes annonces</span>
                        </a>
                    </motion.div>
                ) : (
                    /* CAS 2 & 3 : VISITEUR OU ANNONCE COMPLÉTÉE */
                    <motion.div 
                        className="d-grid gap-3"
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.6 }}
                    >
                        {!isCompleted && !isOwner ? (
                            <motion.button
                                className="btn btn-primary btn-pill btn-lg d-flex align-items-center justify-content-center gap-2"
                                onClick={handleContact}
                                whileHover={{ scale: 1.03, boxShadow: '0 8px 16px rgba(240, 125, 0, 0.3)' }}
                                whileTap={{ scale: 0.97 }}
                            >
                                <MessageCircle size={20} />
                                <span>Contacter le donneur</span>
                            </motion.button>
                        ) : isCompleted ? (
                            <div className="alert alert-info border-0 shadow-sm text-center mb-0 d-flex align-items-center justify-content-center gap-2">
                                <AlertCircle size={20} />
                                <span>Cet objet n'est plus disponible</span>
                            </div>
                        ) : null}

                        <a href="/" className="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">
                            <ArrowLeft size={18} />
                            <span>Retour au catalogue</span>
                        </a>
                    </motion.div>
                )}
            </div>
            </motion.div>
        </>
    );
}
