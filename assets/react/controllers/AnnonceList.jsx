import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Search, Filter } from 'lucide-react';
import { Toaster } from 'react-hot-toast';
import AnnonceCard from '../components/AnnonceCard';

export default function AnnonceList() {
    const [annonces, setAnnonces] = useState([]);
    const [loading, setLoading] = useState(true);
    const [campus, setCampus] = useState('ALL');
    const [searchQuery, setSearchQuery] = useState('');
    const [debouncedQuery, setDebouncedQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        fetch('/api/categories')
            .then(response => response.json())
            .then(data => setCategories(Array.isArray(data) ? data : []))
            .catch(() => setCategories([]));
    }, []);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedQuery(searchQuery.trim());
        }, 500);

        return () => clearTimeout(handler);
    }, [searchQuery]);

    useEffect(() => {
        setLoading(true);

        const params = new URLSearchParams();
        if (campus && campus !== 'ALL') {
            params.set('campus', campus);
        }
        if (selectedCategory) {
            params.set('category', selectedCategory);
        }
        if (debouncedQuery) {
            params.set('q', debouncedQuery);
        }

        const queryString = params.toString();

        fetch(`/api/annonces${queryString ? `?${queryString}` : ''}`)
            .then(response => response.json())
            .then(data => {
                setAnnonces(data);
                setLoading(false);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des annonces:', error);
                setLoading(false);
            });
    }, [campus, selectedCategory, debouncedQuery]);

    const getCampusBadgeClass = (campus) => {
        const badges = {
            'CALAIS': 'bg-primary',
            'DUNKERQUE': 'bg-info',
            'BOULOGNE': 'bg-warning',
            'SAINT_OMER': 'bg-secondary'
        };
        return badges[campus] || 'bg-dark';
    };

    const getTypeBadgeClass = (type) => {
        return type === 'Gratuit' ? 'bg-success' : 'bg-warning';
    };

    const getCampusLabel = (campus) => {
        const labels = {
            'CALAIS': 'Calais',
            'DUNKERQUE': 'Dunkerque',
            'BOULOGNE': 'Boulogne',
            'SAINT_OMER': 'Saint-Omer'
        };
        return labels[campus] || campus;
    };

    return (
        <div className="container py-5">
            <Toaster position="top-right" />
            {/* Hero Section avec Pattern */}
            <motion.div 
                className="bg-hero-pattern rounded-4 mb-5 text-center"
                initial={{ opacity: 0, y: -20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6 }}
            >
                <h1 className="display-4 fw-bold mb-3">
                    Plateforme d’annonces universitaires
                </h1>
                <p className="lead mb-2">
                    Une plateforme sécurisée pour le don et le troc entre étudiants de l’ULCO.
                </p>
                <p className="text-muted mb-0">
                    Recherchez par campus, catégorie ou mots-clés pour trouver rapidement l’offre idéale.
                </p>
            </motion.div>

            {/* Zone de Recherche Moderne */}
            <motion.div 
                className="mb-5"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ delay: 0.2, duration: 0.5 }}
            >
                <div className="card border-0 shadow-sm p-4">
                    <div className="row g-3">
                        {/* Barre de recherche avec icône Lucide */}
                        <div className="col-12 col-lg-5">
                            <div className="position-relative">
                                <Search 
                                    size={20} 
                                    className="position-absolute" 
                                    style={{ left: '12px', top: '50%', transform: 'translateY(-50%)', color: '#6c757d' }}
                                />
                                <input
                                    type="text"
                                    className="form-control ps-5"
                                    placeholder="Rechercher un objet..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </div>
                        
                        {/* Filtre Campus */}
                        <div className="col-12 col-md-6 col-lg-3">
                            <select
                                className="form-select"
                                value={campus}
                                onChange={(e) => setCampus(e.target.value)}
                            >
                                <option value="ALL">🌍 Tous les campus</option>
                                <option value="CALAIS">Calais</option>
                                <option value="DUNKERQUE">Dunkerque</option>
                                <option value="BOULOGNE">Boulogne</option>
                                <option value="SAINT_OMER">Saint-Omer</option>
                            </select>
                        </div>
                        
                        {/* Filtre Catégorie */}
                        <div className="col-12 col-md-6 col-lg-4">
                            <select
                                className="form-select"
                                value={selectedCategory}
                                onChange={(e) => setSelectedCategory(e.target.value)}
                            >
                                <option value="">Toutes les catégories</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Résumé des filtres actifs */}
                    {(campus !== 'ALL' || selectedCategory || debouncedQuery) && (
                        <div className="mt-3 d-flex align-items-center gap-2 flex-wrap">
                            <Filter size={16} style={{ color: '#004E86' }} />
                            <small className="text-muted">Filtres actifs:</small>
                            {campus !== 'ALL' && (
                                <span className="badge badge-eilco">{getCampusLabel(campus)}</span>
                            )}
                            {selectedCategory && (
                                <span className="badge badge-accent">
                                    {categories.find(c => c.id === selectedCategory)?.name}
                                </span>
                            )}
                            {debouncedQuery && (
                                <span className="badge bg-secondary">"{debouncedQuery}"</span>
                            )}
                            <button 
                                className="btn btn-sm btn-link text-decoration-none"
                                onClick={() => {
                                    setCampus('ALL');
                                    setSelectedCategory('');
                                    setSearchQuery('');
                                }}
                            >
                                Réinitialiser
                            </button>
                        </div>
                    )}
                </div>
            </motion.div>

            {/* Zone de Liste avec Animations */}
            {loading ? (
                <motion.div 
                    className="text-center py-5"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                >
                    <div className="spinner-eilco mx-auto mb-3"></div>
                    <p className="text-muted">Chargement des annonces...</p>
                </motion.div>
            ) : annonces.length === 0 ? (
                <motion.div 
                    className="alert alert-info text-center border-0 shadow-sm" 
                    role="alert"
                    initial={{ opacity: 0, scale: 0.9 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={{ duration: 0.4 }}
                >
                    <h5 className="alert-heading">📭 Aucune annonce trouvée</h5>
                    <p className="mb-0">Aucune annonce ne correspond à vos critères de recherche.</p>
                </motion.div>
            ) : (
                <>
                    {/* Nombre de résultats */}
                    <motion.div 
                        className="mb-3"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                    >
                        <p className="text-muted">
                            <strong>{annonces.length}</strong> annonce{annonces.length > 1 ? 's' : ''} trouvée{annonces.length > 1 ? 's' : ''}
                        </p>
                    </motion.div>

                    {/* Grille d'Annonces avec AnnonceCard */}
                    <div className="row g-4">
                        {annonces.map((annonce, index) => (
                            <div key={annonce.id} className="col-12 col-md-6 col-lg-4">
                                <AnnonceCard annonce={annonce} index={index} />
                            </div>
                        ))}
                    </div>
                </>
            )}
        </div>
    );
}
