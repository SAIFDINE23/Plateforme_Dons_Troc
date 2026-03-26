import React, { useEffect, useState } from 'react';
import toast, { Toaster } from 'react-hot-toast';

const CAMPUS_OPTIONS = [
    { value: 'CALAIS', label: 'Calais' },
    { value: 'DUNKERQUE', label: 'Dunkerque' },
    { value: 'BOULOGNE', label: 'Boulogne-sur-Mer' },
    { value: 'SAINT_OMER', label: 'Saint-Omer' },
];

export default function UserManager() {
    const [users, setUsers] = useState([]);
    const [filteredUsers, setFilteredUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [selectedUser, setSelectedUser] = useState(null);
    const [roleChoice, setRoleChoice] = useState('USER');
    const [campusChoice, setCampusChoice] = useState('');
    const [saving, setSaving] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentUserRole, setCurrentUserRole] = useState(null);

    useEffect(() => {
        loadUsers();
        // Récupérer le rôle de l'utilisateur connecté depuis les attributs data
        const userDataElement = document.querySelector('[data-current-user-roles]');
        if (userDataElement) {
            const roles = JSON.parse(userDataElement.dataset.currentUserRoles || '[]');
            setCurrentUserRole(roles.includes('ROLE_RESPONSABLE') ? 'RESPONSABLE' : 'MODERATOR');
        }
    }, []);

    useEffect(() => {
        // Filtrer les utilisateurs selon le terme de recherche
        if (searchTerm.trim() === '') {
            setFilteredUsers(users);
        } else {
            const term = searchTerm.toLowerCase();
            const filtered = users.filter(user => 
                user.cas_uid.toLowerCase().includes(term) ||
                user.email.toLowerCase().includes(term)
            );
            setFilteredUsers(filtered);
        }
    }, [users, searchTerm]);

    const loadUsers = async () => {
        setLoading(true);
        try {
            const response = await fetch('/api/admin/users');
            if (!response.ok) throw new Error('Erreur lors du chargement des utilisateurs');
            const data = await response.json();
            setUsers(data);
            setFilteredUsers(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const getRoleBadge = (roles) => {
        if (roles.includes('ROLE_RESPONSABLE')) {
            return <span className="badge bg-danger">RESPONSABLE</span>;
        }
        if (roles.includes('ROLE_MODERATOR')) {
            return <span className="badge bg-primary">MODÉRATEUR</span>;
        }
        return <span className="badge bg-secondary">UTILISATEUR</span>;
    };

    const openRoleModal = (user) => {
        setSelectedUser(user);
        if (user.roles.includes('ROLE_MODERATOR')) {
            setRoleChoice('MODERATOR');
        } else {
            setRoleChoice('USER');
        }
        const modal = new window.bootstrap.Modal(document.getElementById('roleModal'));
        modal.show();
    };

    const handleSaveRole = async () => {
        if (!selectedUser) return;

        setSaving(true);
        try {
            const response = await fetch(`/api/admin/users/${selectedUser.id}/promote`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    role: roleChoice,
                }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Erreur lors de la mise à jour');
            }

            await loadUsers();
            setSelectedUser(null);
            const modalEl = document.getElementById('roleModal');
            window.bootstrap.Modal.getInstance(modalEl)?.hide();
        } catch (err) {
            setError(err.message);
        } finally {
            setSaving(false);
        }
    };

    const toggleBan = async (user) => {
        try {
            let options = { method: 'POST' };

            if (!user.is_banned) {
                const reason = window.prompt('Veuillez saisir le motif du bannissement (Obligatoire) :');
                if (!reason || !reason.trim()) {
                    return;
                }

                options = {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reason: reason.trim() }),
                };
            }

            const response = await fetch(`/api/admin/users/${user.id}/ban`, options);

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Erreur lors du bannissement');
            }

            await loadUsers();
            if (user.is_banned) {
                toast.success('Compte utilisateur réactivé.');
            } else {
                toast.success('Utilisateur banni et notifié par email.');
            }
        } catch (err) {
            setError(err.message);
        }
    };

    if (loading) {
        return (
            <div className="text-center py-5">
                <div className="spinner-border text-primary" role="status"></div>
            </div>
        );
    }

    return (
        <div className="card shadow-sm">
            <Toaster position="top-right" />
            <div className="card-body">
                {error && (
                    <div className="alert alert-danger" role="alert">
                        <i className="bi bi-exclamation-triangle me-2"></i>
                        {error}
                    </div>
                )}

                {/* Champ de recherche */}
                <div className="mb-3">
                    <div className="input-group">
                        <span className="input-group-text">
                            <i className="bi bi-search"></i>
                        </span>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Rechercher par identifiant ou email..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                        {searchTerm && (
                            <button
                                className="btn btn-outline-secondary"
                                onClick={() => setSearchTerm('')}
                            >
                                <i className="bi bi-x"></i>
                            </button>
                        )}
                    </div>
                    <small className="text-muted">
                        {filteredUsers.length} utilisateur{filteredUsers.length > 1 ? 's' : ''} trouvé{filteredUsers.length > 1 ? 's' : ''}
                    </small>
                </div>

                <div className="table-responsive">
                    <table className="table table-hover align-middle">
                        <thead className="table-light">
                            <tr>
                                <th>Identifiant (CAS)</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th className="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredUsers.map((user) => (
                                <tr key={user.id}>
                                    <td>
                                        <div className="fw-bold">{user.cas_uid}</div>
                                        <div className="small text-muted">{user.email}</div>
                                    </td>
                                    <td>{getRoleBadge(user.roles)}</td>
                                    <td>
                                        {user.is_banned ? (
                                            <span className="badge bg-danger">BANNI</span>
                                        ) : (
                                            <span className="badge bg-success">ACTIF</span>
                                        )}
                                    </td>
                                    <td className="text-end">
                                        <div className="d-flex gap-2 justify-content-end">
                                            {!user.roles.includes('ROLE_RESPONSABLE') && !user.roles.includes('ROLE_MODERATOR') && (
                                                <>
                                                    <button
                                                        className={user.is_banned ? 'btn btn-sm btn-success' : 'btn btn-sm btn-danger'}
                                                        onClick={() => toggleBan(user)}
                                                    >
                                                        {user.is_banned ? 'Réactiver' : '🚫 Bannir'}
                                                    </button>
                                                    {/* Seuls les RESPONSABLE peuvent modifier les rôles */}
                                                    {currentUserRole === 'RESPONSABLE' && (
                                                        <button
                                                            className="btn btn-sm btn-outline-primary"
                                                            onClick={() => openRoleModal(user)}
                                                        >
                                                            ✏️ Modifier rôle
                                                        </button>
                                                    )}
                                                </>
                                            )}
                                            {(user.roles.includes('ROLE_RESPONSABLE') || user.roles.includes('ROLE_MODERATOR')) && (
                                                <span className="badge bg-warning text-dark">Protégé</span>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="modal fade" id="roleModal" tabIndex="-1" aria-hidden="true">
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">Modifier rôle</h5>
                            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div className="modal-body">
                            <div className="mb-3">
                                <label className="form-label">Type de compte</label>
                                <select
                                    className="form-select"
                                    value={roleChoice}
                                    onChange={(e) => setRoleChoice(e.target.value)}
                                >
                                    <option value="USER">Utilisateur (Étudiant / Prof / Personnel)</option>
                                    <option value="MODERATOR">Modérateur (Gère toutes les annonces et bannissements)</option>
                                </select>
                            </div>
                            <div className="alert alert-info small">
                                <i className="bi bi-info-circle me-2"></i>
                                Les modérateurs peuvent gérer les annonces de tous les campus et bannir des utilisateurs.
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <button
                                type="button"
                                className="btn btn-primary"
                                onClick={handleSaveRole}
                                disabled={saving}
                            >
                                {saving ? 'Enregistrement...' : 'Enregistrer'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
