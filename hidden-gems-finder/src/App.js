import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Home from './pages/Home';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import './index.css';

const Particles = () => {
  const embers = [...Array(30)].map((_, i) => ({
    id: i,
    x: `${Math.random() * 100}%`,
    delay: `${Math.random() * 20}s`,
    duration: `${Math.random() * 10 + 15}s`,
    size: `${Math.random() * 3 + 2}px`
  }));

  return (
    <div className="particles-layer">
      {embers.map(e => (
        <div key={e.id} className="ember" style={{ 
          '--x': e.x, 
          '--delay': e.delay, 
          '--d': e.duration, 
          '--s': e.size 
        }} />
      ))}
    </div>
  );
};


function App() {
  return (
    <>
      <div className="ambient-bg">
        <div className="ambient-orb ambient-orb-1" />
        <div className="ambient-orb ambient-orb-2" />
        <div className="ambient-orb ambient-orb-3" />
      </div>
      <Particles />

      <Router>
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/dashboard" element={<Dashboard />} />
        </Routes>
      </Router>
    </>
  );
}

export default App;
