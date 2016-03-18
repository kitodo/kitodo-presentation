/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Goobi. Digitalisieren im Verein e.V. <contact@goobi.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
describe('Test suite for the dlfAltoParser', function() {

    var document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<alto xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' +
        'xmlns="http://www.loc.gov/standards/alto/ns-v2#" xsi:schemaLocation="http://www.loc.gov/standards/alto/ns-v2# ' +
        'http://www.loc.gov/standards/alto/alto-v2.0.xsd"><Layout><Page ID="Page1" PHYSICAL_IMG_NR="1">' +
        '<PrintSpace HEIGHT="1629" WIDTH="1125" VPOS="0" HPOS="0"><TextBlock ID="BlockId-A77C2C7B-CB7F-432C-BA64-99657CEF96BE-" ' +
        'HEIGHT="24" WIDTH="18" VPOS="85" HPOS="104" STYLEREFS="StyleId-070C6916-2652-4C51-8B54-1185B1E5E973- font1">' +
        '<TextLine HEIGHT="15" WIDTH="10" VPOS="89" HPOS="108"><String CONTENT="2" HEIGHT="15" WIDTH="10" VPOS="89" ' +
        'HPOS="108"/></TextLine></TextBlock><ComposedBlock ID="BlockId-E8254DC2-E15F-4181-9CA4-6EA36940863B-" ' +
        'HEIGHT="203" WIDTH="694" VPOS="140" HPOS="103" TYPE="table"><TextBlock ' +
        'ID="BlockId-52809789-89AA-4D70-9BFF-23097229F168-" HEIGHT="29" WIDTH="51" ' +
        'VPOS="140" HPOS="103" STYLEREFS="StyleId-20CD36A1-2BCE-4E7D-AAB2-3AD839533AF2-font1">' +
        '<TextLine HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"><String STYLEREFS="font0" CONTENT="Seite" ' +
        'HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"/></TextLine></TextBlock><TextBlock ' +
        'ID="BlockId-97EA0C09-28B9-453B-AAE6-C7E5E53779F2-" HEIGHT="42" WIDTH="586" VPOS="169" HPOS="211" ' +
        'STYLEREFS="StyleId-20CD36A1-2BCE-4E7D-AAB2-3AD839533AF2- font1"><TextLine HEIGHT="29" WIDTH="559" ' +
        'VPOS="173" HPOS="233"><String CONTENT="Gespräch" HEIGHT="25" WIDTH="94" VPOS="173" HPOS="233"/><SP WIDTH="6" ' +
        'VPOS="173" HPOS="328"/></TextLine></TextBlock></ComposedBlock><TextBlock ' +
        'ID="BlockId-C9FE670C-DC48-44F9-B7EF-7980ADA80614-" HEIGHT="545" WIDTH="918" VPOS="880" HPOS="119" ' +
        'STYLEREFS="StyleId-20CD36A1-2BCE-4E7D-AAB2-3AD839533AF2- font1"><TextLine HEIGHT="32" WIDTH="214" ' +
        'VPOS="883" HPOS="127" STYLEREFS="StyleId-20D43B5F-E91D-4F10-B0E1-B68D39E7C301- font2"><String ' +
        'CONTENT="In" HEIGHT="23" WIDTH="27" VPOS="883" HPOS="127"/><SP WIDTH="9" VPOS="892" HPOS="155"/><String ' +
        'CONTENT="eigener" HEIGHT="31" WIDTH="93" VPOS="884" HPOS="165"/><SP WIDTH="8" VPOS="884" HPOS="259"/><String ' +
        'CONTENT="Sache" HEIGHT="24" WIDTH="73" VPOS="883" HPOS="268"/></TextLine><TextLine HEIGHT="25" WIDTH="878" ' +
        'VPOS="935" HPOS="126"><String CONTENT="Seit" HEIGHT="19" WIDTH="37" VPOS="935" HPOS="126"/>' +
        '<SP WIDTH="6" VPOS="942" HPOS="164"/><String CONTENT="vier" HEIGHT="19" WIDTH="38" VPOS="936" HPOS="171"/>' +
        '<SP WIDTH="3" VPOS="936" HPOS="210"/></TextLine><TextLine HEIGHT="27" WIDTH="904" VPOS="967" HPOS="126">' +
        '<String CONTENT="die" HEIGHT="19" WIDTH="30" VPOS="967" HPOS="126"/><SP WIDTH="6" VPOS="968" HPOS="157"/>' +
        '<String CONTENT="Unterstützung" HEIGHT="26" WIDTH="148" VPOS="968" HPOS="164"/><SP WIDTH="5" VPOS="976" ' +
        'HPOS="313"/></TextLine></TextBlock><ComposedBlock ID="BlockId-557B27A6-D4C2-4A5A-9F2D-BA0B8E97B692-" ' +
        'HEIGHT="599" WIDTH="964" VPOS="856" HPOS="97" TYPE="separatorsBox"><GraphicalElement ' +
        'ID="BlockId-30DBD37F-D27E-4AFB-9A01-507405CEC5A0-" HEIGHT="9" WIDTH="959" VPOS="856" HPOS="102"/>' +
        '<GraphicalElement ID="BlockId-B07F29BB-604A-4675-B005-7E445C8E9155-" HEIGHT="7" WIDTH="746" VPOS="1446" ' +
        'HPOS="97"/></ComposedBlock><GraphicalElement ID="BlockId-D2E23C8E-2A3C-4E42-AE0A-F8361AD0784E-" HEIGHT="6" ' +
        'WIDTH="704" VPOS="119" HPOS="107"/><GraphicalElement ID="BlockId-38FD417B-72B6-4AA7-AF8D-3E19ABD7D81B-" ' +
        'HEIGHT="3" WIDTH="256" VPOS="124" HPOS="811"/></PrintSpace></Page></Layout></alto>';

    var textblock = '<TextBlock ID="BlockId-52809789-89AA-4D70-9BFF-23097229F168-" HEIGHT="29" WIDTH="51" VPOS="140" ' +
        'HPOS="103" STYLEREFS="StyleId-20CD36A1-2BCE-4E7D-AAB2-3AD839533AF2- font1">' +
        '<TextLine HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107">' +
        '<String STYLEREFS="font0" CONTENT="Seite" HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"/>' +
        '<SP WIDTH="9" VPOS="892" HPOS="155"/><String CONTENT="eigener" HEIGHT="31" WIDTH="93" VPOS="884" HPOS="165"/>' +
        '<HYP WIDTH="8" VPOS="884" HPOS="259"/><String CONTENT="Sache" HEIGHT="24" WIDTH="73" VPOS="883" HPOS="268"/>' +
        '</TextLine></TextBlock>';

    describe('Test function - parseXML_', function() {

        it('Input xml is of type string', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_(document);
            expect(parser.parseXML_(testDoc) instanceof XMLDocument).toBe(true);
        });

        it('Input xml is of type XMLDocument', function() {
            var parser = new dlfAltoParser(),
                testDoc = $.parseXML(document);
            expect(parser.parseXML_(testDoc) instanceof XMLDocument).toBe(true);
        });

    });

    describe('Test function - parseGeometry_', function() {

        it('Proper working for correct element', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_('<TextLine HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"></TextLine>'),
                element = $(testDoc).find('TextLine'),
                geometry = parser.parseGeometry_(element[0]);
            expect(typeof geometry).toBe('object');
            expect(geometry instanceof ol.geom.Polygon).toBe(true);
        });

    });

    describe('Test function - parseFeatureWithGeometry_', function() {

        it('Proper working for correct element <TextLine>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_('<TextLine HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"></TextLine>'),
                element = $(testDoc).find('TextLine'),
                feature = parser.parseFeatureWithGeometry_(element[0]);
            expect(typeof feature).toBe('object');
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('height')).toBe(15);
            expect(feature.get('width')).toBe(40);
            expect(feature.get('vpos')).toBe(144);
            expect(feature.get('hpos')).toBe(107);
            expect(feature.get('type')).toBe('textline');
        });

        it('Proper working for correct element <TextBlock>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_('<TextBlock HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"></TextBlock>'),
                element = $(testDoc).find('TextBlock'),
                feature = parser.parseFeatureWithGeometry_(element[0]);
            expect(typeof feature).toBe('object');
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('height')).toBe(15);
            expect(feature.get('width')).toBe(40);
            expect(feature.get('vpos')).toBe(144);
            expect(feature.get('hpos')).toBe(107);
            expect(feature.get('type')).toBe('textblock');
        });

    });

    describe('Test function - parseAltoFeature_', function() {

        it('Proper working for correct element <TextLine>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_('<TextLine HEIGHT="15" WIDTH="40" VPOS="144" HPOS="107"></TextLine>'),
                element = $(testDoc).find('TextLine'),
                feature = parser.parseAltoFeature_(element[0]);
            expect(typeof feature).toBe('object');
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('height')).toBe(15);
            expect(feature.get('width')).toBe(40);
            expect(feature.get('vpos')).toBe(144);
            expect(feature.get('hpos')).toBe(107);
            expect(feature.get('type')).toBe('textline');
        });

        it('Proper working for correct element <Page>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_('<Page></Page>'),
                element = $(testDoc).find('Page'),
                feature = parser.parseAltoFeature_(element[0]);
            expect(typeof feature).toBe('object');
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('type')).toBe('page');
        });

    });

    describe('Test function - parseTextBlockFeatures_', function() {

        it('Proper working for correct element <TextBlock>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_(textblock),
                features = parser.parseTextBlockFeatures_(testDoc);

            expect(features.length).toBe(1);

            var feature = features[0];
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('type')).toBe('textblock');

            // get TextBlock / ComposedBlock elements
            var textline = feature.get('textlines');
            expect(textline.length).toBe(1);
        });

    });

    describe('Test function - parseTextLineFeatures_', function() {

        it('Proper working for correct element <TextLine>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_(textblock),
                features = parser.parseTextLineFeatures_(testDoc);

            expect(features.length).toBe(1);

            var feature = features[0];
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('type')).toBe('textline');
        });

    });

    describe('Test function - parsePrintSpaceFeature_', function() {

        it('Proper working for correct element <PrintSpace>', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_(document),
                feature = parser.parsePrintSpaceFeature_(testDoc);

            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('type')).toBe('printspace');

            // get TextBlock / ComposedBlock elements
            var blocks = feature.get('textblocks');
            expect(blocks.length).toBe(4);
        });

        it('Proper working for correct element <PrintSpace> with empty node', function() {
            var parser = new dlfAltoParser(),
                testDoc = parser.parseXML_('<test></test>'),
                feature = parser.parsePrintSpaceFeature_(testDoc);

            expect(feature).toBe(undefined);
        });

    });

    describe('Test function - parseFeatures', function() {

        it('Proper working of complete parsing', function() {
            var parser = new dlfAltoParser(),
                features = parser.parseFeatures(document);

            expect(features.length).toBe(1);

            // check if root type is page
            var feature = features[0];
            expect(feature instanceof ol.Feature).toBe(true);
            expect(feature.get('type')).toBe('page');

            // get PrintSpace
            var printspace = feature.get('printspace');
            expect(printspace instanceof ol.Feature).toBe(true);
            expect(printspace.get('height')).toBe(1629);
            expect(printspace.get('width')).toBe(1125);
            expect(printspace.get('vpos')).toBe(0);
            expect(printspace.get('hpos')).toBe(0);
            expect(printspace.get('type')).toBe('printspace');

            // get TextBlock / ComposedBlock elements
            var blocks = printspace.get('textblocks');
            expect(blocks.length).toBe(4);
        });
    });
});