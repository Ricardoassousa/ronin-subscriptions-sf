import React, { useEffect, useRef, useState } from 'react'; 
import { Chart, LineController, LineElement, PointElement, CategoryScale, LinearScale, Title, Tooltip, Legend } from 'chart.js'; 
import type { ChartType, ChartData, ChartOptions } from 'chart.js';

// Register the necessary components for Chart.js
Chart.register(
    LineController,  // This is the controller for line charts (needed for Chart.js v3+)
    LineElement,     // This is used for the line itself in the chart
    PointElement,    // This is used for the data points on the line
    CategoryScale,   // This is used for the x-axis scale (usually categorical data)
    LinearScale,     // This is used for the y-axis scale (usually numerical data)
    Title,           // This is for the chart's title
    Tooltip,         // This enables tooltips when hovering over data points
    Legend           // This displays the legend (e.g., for datasets)
);

interface GrowthChartProps {
    apiUrl: string;  // The URL to fetch the chart data from
}

const GrowthChart: React.FC<GrowthChartProps> = ({ apiUrl }) => {
    // References to the canvas and the chart instance for managing chart updates
    const chartRef = useRef<HTMLCanvasElement | null>(null);
    const chartInstanceRef = useRef<Chart | null>(null); 

    // State to manage errors (e.g., if data fails to load)
    const [error, setError] = useState<string | null>(null);

    // Function to fetch data from the API
    const fetchData = async () => {
        try {
            // console.log('Fetching data from:', apiUrl);  // Log the API URL to see where it's coming from
            const response = await fetch(apiUrl);  // Fetch data from the provided API URL
            const data: ChartData<'line', number[], string> = await response.json();  // Parse the response into a Chart.js-friendly format

            // If there is already an existing chart, destroy it to avoid memory leaks and multiple charts rendering
            if (chartInstanceRef.current) {
                // console.log('Destroying previous chart instance:', chartInstanceRef.current);
                chartInstanceRef.current.destroy();  // This ensures that the old chart instance is properly cleaned up
            }

            // Create a new chart with the data received from the API
            if (chartRef.current) {
                const ctx = chartRef.current.getContext('2d');  // Get the 2D drawing context from the canvas
                if (ctx) {
                    // Create a new instance of the Chart.js chart
                    const newChart = new Chart(ctx, {
                    type: 'line' as ChartType,  // Define the type of chart (line chart in this case)
                    data: data,  // The data for the chart (fetched from the API)
                    options: {
                        responsive: true,  // Make the chart responsive (it will adjust to container size)
                        maintainAspectRatio: false,  // Disable aspect ratio maintenance (allow flexible resizing)
                        plugins: {
                            legend: { position: 'top' },  // Position the legend at the top
                            title: { display: true, text: 'Monthly New Subscriptions' },  // Set the title of the chart
                        },
                        scales: {
                            y: { beginAtZero: true },  // Ensure the y-axis starts at 0
                        },
                    } as ChartOptions,  // Provide the chart options with proper types
                });

            // console.log('New chart created:', newChart);  // Log the new chart instance for debugging
            chartInstanceRef.current = newChart;  // Store the new chart instance in the ref to access it later
            }
        }
    } catch (err) {
        // console.error('Error fetching growth data:', err);  // Log any errors that occur during the data fetch
        setError('Failed to load growth data. Please try again later.');  // Display an error message if the data can't be fetched
    }
};

useEffect(() => {
    // This effect will run once when the component is mounted and then every 30 seconds
    fetchData();  // Fetch the initial data when the component mounts
    const interval = setInterval(fetchData, 30000);  // Set up an interval to refresh the data every 30 seconds

    // Cleanup function to clear the interval when the component is unmounted
    return () => {
        clearInterval(interval);  // Clear the interval to avoid memory leaks
        if (chartInstanceRef.current) {
            // console.log('Cleaning up chart instance on component unmount:', chartInstanceRef.current);
            chartInstanceRef.current.destroy();  // Destroy the chart instance to avoid memory leaks
        }
    };
}, [apiUrl]);  // This dependency ensures that the effect will re-run if the API URL changes

return (
    <div style={{ height: '400px', width: '100%' }}>
        {error && <div style={{ color: 'red', marginBottom: '10px' }}>{error}</div>}  {/* Display the error message if there was an issue */}
        <canvas ref={chartRef}></canvas>  {/* The canvas where the chart will be rendered */}
    </div>
    );
};

export default GrowthChart;  // Export the component to be used elsewhere