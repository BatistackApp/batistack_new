import 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js';
import Gantt from 'frappe-gantt';
import { IfcViewerAPI } from 'web-ifc-viewer';
import * as THREE from 'three';
import DxfParser from 'dxf-parser';
import { DxfViewer } from 'dxf-viewer';

window.Gantt = Gantt;
window.IfcViewerAPI = IfcViewerAPI;
window.THREE = THREE;
window.DxfParser = DxfParser;
window.DxfViewer = DxfViewer;
