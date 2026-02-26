import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from './store/authStore';
import Layout from './components/Layout';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import CharacterSelectionPage from './pages/CharacterSelectionPage';
import CharacterCreationPage from './pages/CharacterCreationPage';
import DashboardPage from './pages/DashboardPage';
import CombatPage from './pages/CombatPage';
import InventoryPage from './pages/InventoryPage';
import ShopsPage from './pages/ShopsPage';

function App() {
  const { isAuthenticated } = useAuthStore();

  return (
    <Router>
      <Layout>
        <Routes>
          {/* Public Routes */}
          <Route
            path="/login"
            element={!isAuthenticated ? <LoginPage /> : <Navigate to="/dashboard" />}
          />
          <Route
            path="/register"
            element={!isAuthenticated ? <RegisterPage /> : <Navigate to="/dashboard" />}
          />

          {/* Protected Routes */}
          <Route
            path="/dashboard"
            element={isAuthenticated ? <DashboardPage /> : <Navigate to="/login" />}
          />
          <Route
            path="/characters"
            element={isAuthenticated ? <CharacterSelectionPage /> : <Navigate to="/login" />}
          />
          <Route
            path="/characters/create"
            element={isAuthenticated ? <CharacterCreationPage /> : <Navigate to="/login" />}
          />
          <Route
            path="/combat"
            element={isAuthenticated ? <CombatPage /> : <Navigate to="/login" />}
          />
          <Route
            path="/inventory"
            element={isAuthenticated ? <InventoryPage /> : <Navigate to="/login" />}
          />
          <Route
            path="/shops"
            element={isAuthenticated ? <ShopsPage /> : <Navigate to="/login" />}
          />

          {/* Redirects */}
          <Route path="/" element={<Navigate to={isAuthenticated ? "/dashboard" : "/login"} />} />
        </Routes>
      </Layout>
    </Router>
  );
}

export default App;
