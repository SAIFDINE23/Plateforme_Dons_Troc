import React, { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { MapPin, Tag, Clock, Eye, Heart } from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import toast from 'react-hot-toast';

/**
 * Composant AnnonceCard - Carte d'annonce moderne avec animations
 * Design institutionnel EILCO avec Framer Motion et Lucide Icons
 */
const AnnonceCard = ({ annonce, index = 0 }) => {
    const [isFavorite, setIsFavorite] = useState(!!annonce.isFavorite);

    useEffect(() => {
        setIsFavorite(!!annonce.isFavorite);
    }, [annonce.isFavorite, annonce.id]);

    // Animation variants pour l'apparition progressive
    const cardVariants = {
        hidden: { 
            opacity: 0, 
            y: 20 
        },
        visible: { 
            opacity: 1, 
            y: 0,
            transition: {
                duration: 0.5,
                delay: index * 0.1, // Stagger effect
                ease: [0.4, 0, 0.2, 1]
            }
        }
    };

    // Formater la date de publication
    const formatDate = (dateString) => {
        if (!dateString) return 'Date inconnue';
        
        const date = new Date(dateString);
        
        // Vérifier si la date est valide
        if (isNaN(date.getTime())) {
            return 'Date invalide';
        }
        
        return new Intl.DateTimeFormat('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(date);
    };

    // Générer l'URL de l'image
    const imageUrl = annonce.photoFilename 
        ? `/uploads/annonces/${annonce.photoFilename}`
        : 'https://via.placeholder.com/400x200/004E86/FFFFFF?text=Pas+d\'image';

    // Badge de statut avec couleur
    const getStatusBadge = (state) => {
        const badges = {
            PUBLISHED: { label: 'Publié', class: 'status-published' },
            PENDING: { label: 'En attente', class: 'status-pending' },
            REJECTED: { label: 'Rejeté', class: 'status-rejected' },
            COMPLETED: { label: 'Terminé', class: 'status-completed' }
        };
        return badges[state] || { label: state, class: 'status-pending' };
    };

    const status = getStatusBadge(annonce.state);

    const handleToggleFavorite = async (event) => {
        event.preventDefault();
        event.stopPropagation();

        const nextValue = !isFavorite;
        setIsFavorite(nextValue);

        try {
            const response = await fetch(`/api/annonces/${annonce.id}/favorite`, {
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

    return (
        <motion.div
            variants={cardVariants}
            initial="hidden"
            animate="visible"
            whileHover={{ 
                scale: 1.02,
                transition: { duration: 0.2 }
            }}
            className="h-100"
        >
            <div className="card card-annonce">
                {/* Image de l'annonce */}
                <div className="position-relative overflow-hidden">
                    <img 
                        src={imageUrl}
                        className="card-img-top"
                        alt={annonce.title}
                        style={{ 
                            objectFit: 'cover',
                            height: '200px',
                            transition: 'transform 0.3s ease'
                        }}
                        onMouseEnter={(e) => e.target.style.transform = 'scale(1.05)'}
                        onMouseLeave={(e) => e.target.style.transform = 'scale(1)'}
                    />
                    
                    {/* Badge de type (Don/Troc) */}
                    <div className="position-absolute top-0 end-0 m-2">
                        <span className={`badge ${annonce.isDonation ? 'bg-success' : 'badge-accent'}`}>
                            {annonce.isDonation ? '🎁 Don' : '🔄 Troc'}
                        </span>
                    </div>

                    {/* Badge de statut */}
                    {annonce.state !== 'PUBLISHED' && (
                        <div className="position-absolute bottom-0 start-0 m-2">
                            <span className={`status-badge ${status.class}`}>
                                {status.label}
                            </span>
                        </div>
                    )}
                </div>

                {/* Corps de la carte */}
                <div className="card-body">
                    {/* Titre */}
                    <h5 className="card-title mb-2">
                        {annonce.title}
                    </h5>

                    {/* Description tronquée */}
                    <div className="card-text text-muted mb-3 markdown-content markdown-content--compact">
                        <ReactMarkdown>
                            {annonce.description.length > 120 
                                ? `${annonce.description.substring(0, 120)}...` 
                                : annonce.description}
                        </ReactMarkdown>
                    </div>

                    {/* Métadonnées avec icônes Lucide */}
                    <div className="d-flex flex-column gap-2 small">
                        {/* Campus */}
                        <div className="d-flex align-items-center text-muted">
                            <MapPin size={16} className="me-2" style={{ color: '#009FE3' }} />
                            <span className="fw-medium">
                                {annonce.campus || 'Non spécifié'}
                            </span>
                        </div>

                        {/* Catégorie */}
                        {annonce.category && (
                            <div className="d-flex align-items-center text-muted">
                                <Tag size={16} className="me-2" style={{ color: '#F07D00' }} />
                                <span>{annonce.category.name}</span>
                            </div>
                        )}

                        {/* Date de publication */}
                        {annonce.createdAt && (
                            <div className="d-flex align-items-center text-muted">
                                <Clock size={16} className="me-2" style={{ color: '#004E86' }} />
                                <span>{formatDate(annonce.createdAt)}</span>
                            </div>
                        )}
                        {annonce.date && !annonce.createdAt && (
                            <div className="d-flex align-items-center text-muted">
                                <Clock size={16} className="me-2" style={{ color: '#004E86' }} />
                                <span>{annonce.date}</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Footer avec bouton d'action et favoris */}
                <div className="card-footer bg-white border-top-0 d-flex gap-2">
                    <button
                        type="button"
                        className="btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0"
                        onClick={handleToggleFavorite}
                        aria-label={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                        style={{ width: '42px', height: '42px' }}
                        title={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                    >
                        <i className={`bi ${isFavorite ? 'bi-heart-fill' : 'bi-heart'}`} style={{ fontSize: '1.2rem', color: isFavorite ? '#F07D00' : '#6c757d' }}></i>
                    </button>
                    <a 
                        href={`/annonce/${annonce.id}`} 
                        className="btn btn-primary btn-pill flex-grow-1 d-flex align-items-center justify-content-center gap-2"
                    >
                        <Eye size={18} />
                        <span>Voir l'annonce</span>
                    </a>
                </div>
            </div>
        </motion.div>
    );
};

export default AnnonceCard;
