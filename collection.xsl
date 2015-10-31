<!-- Return department(s) with highest salary -->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" version="1.0">
    <xsl:template match="/">
        <section>
			<xsl:variable name="max-salary" select="/company/department/employee/@salary"/>
        	a<xsl:copy-of select="/company/department[employee/@salary = $max-salary]"/>
		</section>
    </xsl:template>
</xsl:transform>