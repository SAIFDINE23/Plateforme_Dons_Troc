import React, { useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import { Heart, ArrowRight } from 'lucide-react';
import { Toaster } from 'react-hot-toast';
import AnnonceCard from '../components/AnnonceCard';

export default function FavoritesList() {
    const [favorites, setFavorites] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/api/favorites')
            .then((response) => response.json())
            .then((data) => {
                setFavorites(Array.isArray(data) ? data : []);
                setLoading(false);
            })
            .catch(() => {
                setFavorites([]);
                setLoading(false);
            });
    }, []);

    if (loading) {
        return (
            <motion.div
                className="text-center py-5"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
            >
                <div className="spinner-eilco mx-auto mb-3"></div>
                <p className="text-muted">Chargement de vos favoris...</p>
            </motion.div>
        );
    }

    return (
        <div className="container py-5">
            <Toaster position="top-right" />

            <motion.div
                className="bg-hero-pattern rounded-4 mb-5 text-center"
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6 }}
            >
                <h1 className="display-5 fw-bold mb-3">Mes favoris</h1>
                <p className="lead mb-0">
                    Retrouvez rapidement les annonces que vous avez enregistrées.
                </p>
            </motion.div>

            {favorites.length === 0 ? (
                <motion.div
                    className="card border-0 shadow-sm p-5 text-center"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                >
                    <div className="mx-auto mb-3" style={{ color: '#F07D00' }}>
                        <Heart size={36} />
                    </div>
                    <h5 className="mb-2">Aucun favori pour le moment</h5>
                    <p className="text-muted mb-4">
                        Ajoutez des annonces à vos favoris pour les retrouver facilement.
                    </p>
                    <a href="/" className="btn btn-primary btn-pill d-inline-flex align-items-center gap-2">
                        <span>Explorer les annonces</span>
                        <ArrowRight size={18} />
                    </a>
                </motion.div>
            ) : (
                <div className="row g-4">
                    {favorites.map((annonce, index) => (
                        <div className="col-12 col-md-6 col-lg-4" key={annonce.id}>
                            <AnnonceCard annonce={annonce} index={index} />
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
