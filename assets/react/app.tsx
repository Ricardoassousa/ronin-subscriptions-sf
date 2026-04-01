import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import GrowthChart from './components/GrowthChart';

const App: React.FC = () => {
    return (
        <div>
            <div id="growth-chart-root" style={{ height: '400px' }}>
                <GrowthChart apiUrl="/admin/analytics/growth" />
            </div>
        </div>
    );
};

// Não use StrictMode
const container = document.getElementById('growth-chart-root');
if (container) {
    const root = createRoot(container);
    root.render(<App />);
}