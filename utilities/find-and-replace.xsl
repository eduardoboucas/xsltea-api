<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- Find and replace. Stolen from http://geekswithblogs.net/Erik/archive/2008/04/01/120915.aspx -->
<xsl:template name="find-and-replace">
	<xsl:param name="text" />
	<xsl:param name="replace" />
	<xsl:param name="by" />
	<xsl:choose>
		<xsl:when test="contains($text, $replace)">
			<xsl:value-of select="substring-before($text,$replace)" />
			<xsl:value-of select="$by" />
			<xsl:call-template name="find-and-replace">
				<xsl:with-param name="text" select="substring-after($text,$replace)" />
				<xsl:with-param name="replace" select="$replace" />
				<xsl:with-param name="by" select="$by" />
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$text" />
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="find-and-replace-no-escape">
	<xsl:param name="text" />
	<xsl:param name="replace" />
	<xsl:param name="by" />
	<xsl:choose>
		<xsl:when test="contains($text, $replace)">
			<xsl:value-of select="substring-before($text,$replace)" />
			<xsl:value-of disable-output-escaping="yes" select="$by" />
			<xsl:call-template name="find-and-replace-no-escape">
				<xsl:with-param name="text" select="substring-after($text,$replace)" />
				<xsl:with-param name="replace" select="$replace" />
				<xsl:with-param name="by" select="$by" />
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$text" />
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="replace-double-quotes">

<xsl:param name="text"/>
<xsl:variable name="apos">'</xsl:variable>
<xsl:variable name="double_quote"><xsl:text>"</xsl:text></xsl:variable>

<xsl:for-each select="$text">  
        <xsl:variable name="index">
            <xsl:value-of select="position() - 1"/>
        </xsl:variable>
        <xsl:variable name="safeTitle" select="translate(text(),$double_quote, $apos)" /><!-- prevents double quotes from being used inside an array using double quotes -->           
       <xsl:value-of select="$safeTitle" />
    </xsl:for-each>
</xsl:template>

</xsl:stylesheet>
